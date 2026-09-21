<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Money;
use App\Core\Request;
use App\Core\Response;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Tag;
use App\Models\TeamMember;
use App\Policies\ExpensePolicy;
use App\Services\ActivityLogService;
use App\Services\CsvExportService;
use App\Services\ExpenseService;
use App\Services\NotificationService;
use App\Services\ReceiptUploadService;
use InvalidArgumentException;

final class ExpenseController extends Controller
{
    private Expense $expenses;
    private ExpenseService $service;

    public function __construct()
    {
        $this->expenses = new Expense();
        $this->service = new ExpenseService();
    }

    private function filtersFromRequest(Request $request): array
    {
        return [
            'search' => (string) $request->query('search', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'category_id' => $request->query('category_id'),
            'tag_id' => $request->query('tag_id'),
            'project_id' => $request->query('project_id'),
            'paid_by_user_id' => $request->query('paid_by_user_id'),
            'status' => (string) $request->query('status', ''),
            'has_receipt' => $request->query('has_receipt'),
            'min_amount' => $request->query('min_amount'),
            'max_amount' => $request->query('max_amount'),
            'sort' => (string) $request->query('sort', 'expense_date'),
            'direction' => (string) $request->query('direction', 'desc'),
        ];
    }

    public function index(Request $request): void
    {
        $teamId = $this->teamId();
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 20);

        $filters = $this->filtersFromRequest($request);
        [$visSql, $visParams] = ExpensePolicy::visibilitySql($this->role(), $this->userId());

        $result = $this->expenses->listForTeam($teamId, $filters, $visSql, $visParams, $page, $perPage);

        Response::view('expenses/index', [
            'title' => 'Expenses',
            'expenses' => $result['rows'],
            'meta' => $this->paginationMeta($result['total'], $page, $perPage ?: 20),
            'filters' => $filters,
            'categories' => (new Category())->listForTeam($teamId, false),
            'projects' => (new Project())->listForTeam($teamId, [], 1, 100),
            'members' => (new TeamMember())->listForTeam($teamId),
            'tags' => (new Tag())->listForTeam($teamId),
            'role' => $this->role(),
            'queryString' => http_build_query(array_filter($filters)),
        ]);
    }

    public function showCreate(Request $request): void
    {
        if (!$this->can('expense.create')) {
            Response::abort(403);
        }
        $teamId = $this->teamId();
        Response::view('expenses/form', [
            'title' => 'Add Expense',
            'expense' => null,
            'splits' => [],
            'tags' => [],
            'categories' => (new Category())->listForTeam($teamId, false),
            'projects' => (new Project())->listForTeam($teamId, ['status' => 'active'], 1, 100),
            'members' => (new TeamMember())->listForTeam($teamId),
        ]);
    }

    private function buildSplitsFromRequest(Request $request, int $totalCents): array
    {
        $splitType = (string) $request->input('split_type', 'equal');
        $participantIds = array_map('intval', (array) $request->input('participants', []));

        $exact = [];
        $percentages = [];
        $shares = [];
        foreach ($participantIds as $uid) {
            $exact[$uid] = (string) $request->input("exact_amount_$uid", '0');
            $percentages[$uid] = (string) $request->input("percentage_$uid", '0');
            $shares[$uid] = (int) $request->input("shares_$uid", 0);
        }

        return $this->service->calculateSplit($splitType, $totalCents, $participantIds, $exact, $percentages, $shares);
    }

    public function store(Request $request): void
    {
        if (!$this->can('expense.create')) {
            Response::abort(403);
        }
        $teamId = $this->teamId();
        $data = $request->all();

        $validator = new \App\Core\Validator($data);
        $validator->required('title', 'Title')->string('title', 'Title', 1, 180)
            ->required('amount', 'Amount')->money('amount', 'Amount')->min('amount', 'Amount', 0.01)
            ->required('expense_date', 'Expense date')
            ->required('paid_by_user_id', 'Payer');

        $intendedStatus = (string) $request->input('action', 'draft') === 'submit' ? 'submitted' : 'draft';

        $members = new TeamMember();
        if (!empty($data['paid_by_user_id']) && !$members->isActiveMember($teamId, (int) $data['paid_by_user_id'])) {
            $validator->errors();
        }

        if ($validator->fails()) {
            $this->respond($request, false, 'Please correct the errors below.', ['errors' => $validator->errors()], '/expenses/create');
            return;
        }

        try {
            $totalCents = Money::toCents((string) $data['amount']);
            $splitRows = $intendedStatus === 'draft' && empty($data['participants'])
                ? []
                : $this->buildSplitsFromRequest($request, $totalCents);

            $categoryId = !empty($data['category_id']) ? (int) $data['category_id'] : null;
            $projectId = !empty($data['project_id']) ? (int) $data['project_id'] : null;
            if ($categoryId && !(new Category())->belongsToTeam($categoryId, $teamId)) {
                throw new InvalidArgumentException('Invalid category.');
            }
            if ($projectId && !(new Project())->belongsToTeam($projectId, $teamId)) {
                throw new InvalidArgumentException('Invalid project.');
            }

            $expenseId = $this->service->create([
                'title' => trim($data['title']),
                'description' => $data['description'] ?? null,
                'amount' => (string) $data['amount'],
                'currency' => $data['currency'] ?? 'USD',
                'expense_date' => $data['expense_date'],
                'paid_by_user_id' => (int) $data['paid_by_user_id'],
                'created_by' => $this->userId(),
                'category_id' => $categoryId,
                'project_id' => $projectId,
                'split_type' => $data['split_type'] ?? 'equal',
                'status' => $intendedStatus,
            ], $splitRows, (array) ($data['tags'] ?? []), $teamId, $this->userId());

            $this->handleReceiptUploads($request, $expenseId, $teamId);

            if ($intendedStatus === 'submitted') {
                $this->service->submit($expenseId, $teamId, $this->userId());
                $participantIds = array_column($splitRows, 'user_id');
                $recipients = array_diff($participantIds, [$this->userId()]);
                if ($recipients) {
                    (new NotificationService())->notifyMany($recipients, $teamId, 'expense_assigned', 'Added to an expense', 'You were added to an expense.', "/expenses/$expenseId");
                }
                $admins = array_column(array_filter($members->listForTeam($teamId), fn($m) => in_array($m['role'], ['owner', 'admin'], true) && (int) $m['user_id'] !== $this->userId()), 'user_id');
                if ($admins) {
                    (new NotificationService())->notifyMany($admins, $teamId, 'expense_submitted', 'Expense awaiting approval', trim($data['title']) . ' is awaiting approval.', "/expenses/$expenseId");
                }
            }

            $this->respond($request, true, 'Expense saved.', ['id' => $expenseId], "/expenses/$expenseId");
        } catch (InvalidArgumentException $e) {
            $this->respond($request, false, $e->getMessage(), null, '/expenses/create');
        }
    }

    private function handleReceiptUploads(Request $request, int $expenseId, int $teamId): void
    {
        $files = $request->files('receipts');
        if (empty($files)) {
            return;
        }
        $existing = (new \App\Models\Receipt())->countForExpense($expenseId);
        $uploader = new ReceiptUploadService();
        foreach (array_slice($files, 0, max(0, 10 - $existing)) as $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            try {
                $meta = $uploader->store($file, $expenseId, $this->userId(), $teamId);
                $this->expenses->execute(
                    'INSERT INTO expense_receipts (expense_id, original_filename, stored_filename, file_path, mime_type, file_size, uploaded_by, created_at)
                     VALUES (:expense_id, :original_filename, :stored_filename, :file_path, :mime_type, :file_size, :uploaded_by, :created_at)',
                    $meta
                );
                (new ActivityLogService())->log($teamId, $this->userId(), 'receipt.uploaded', 'expense', $expenseId, 'Receipt uploaded.');
            } catch (\Throwable $e) {
                \App\Core\Logger::error('Receipt upload failed: ' . $e->getMessage());
            }
        }
    }

    public function show(Request $request, string $id): void
    {
        $expense = $this->expenses->findDetailed((int) $id);
        if ($expense === null || (int) $expense['team_id'] !== $this->teamId()) {
            Response::abort(404);
        }
        $splits = $this->expenses->splits((int) $id);
        $isParticipant = in_array($this->userId(), array_column($splits, 'user_id'), true);
        if (!ExpensePolicy::canView($expense, $this->role(), $this->userId(), $isParticipant)) {
            Response::abort(403);
        }
        $settings = (new \App\Models\TeamSettings())->forTeam($this->teamId());

        Response::view('expenses/show', [
            'title' => $expense['title'],
            'expense' => $expense,
            'splits' => $splits,
            'tags' => $this->expenses->tags((int) $id),
            'receipts' => $this->expenses->receipts((int) $id),
            'canEdit' => ExpensePolicy::canEdit($expense, $this->role(), $this->userId()),
            'canDelete' => ExpensePolicy::canDelete($expense, $this->role(), $this->userId()),
            'canApprove' => ExpensePolicy::canApprove($expense, $this->role(), $this->userId(), (bool) $settings['allow_self_approval']),
            'role' => $this->role(),
        ]);
    }

    public function showEdit(Request $request, string $id): void
    {
        $expense = $this->expenses->findDetailed((int) $id);
        if ($expense === null || (int) $expense['team_id'] !== $this->teamId()) {
            Response::abort(404);
        }
        if (!ExpensePolicy::canEdit($expense, $this->role(), $this->userId())) {
            Response::abort(403);
        }
        $teamId = $this->teamId();
        Response::view('expenses/form', [
            'title' => 'Edit Expense',
            'expense' => $expense,
            'splits' => $this->expenses->splits((int) $id),
            'tags' => array_column($this->expenses->tags((int) $id), 'name'),
            'categories' => (new Category())->listForTeam($teamId, false),
            'projects' => (new Project())->listForTeam($teamId, ['status' => 'active'], 1, 100),
            'members' => (new TeamMember())->listForTeam($teamId),
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $expense = $this->expenses->find((int) $id);
        if ($expense === null || (int) $expense['team_id'] !== $this->teamId()) {
            Response::abort(404);
        }
        if (!ExpensePolicy::canEdit($expense, $this->role(), $this->userId())) {
            Response::abort(403);
        }

        $teamId = $this->teamId();
        $data = $request->all();

        try {
            $totalCents = Money::toCents((string) $data['amount']);
            $splitRows = $this->buildSplitsFromRequest($request, $totalCents);

            $wasApproved = in_array($expense['status'], ['approved', 'reimbursed'], true)
                && in_array($this->role(), ['owner', 'admin'], true);

            $this->service->update((int) $id, [
                'title' => trim($data['title']),
                'description' => $data['description'] ?? null,
                'amount' => (string) $data['amount'],
                'expense_date' => $data['expense_date'],
                'paid_by_user_id' => (int) $data['paid_by_user_id'],
                'category_id' => !empty($data['category_id']) ? (int) $data['category_id'] : null,
                'project_id' => !empty($data['project_id']) ? (int) $data['project_id'] : null,
                'split_type' => $data['split_type'] ?? 'equal',
            ], $splitRows, (array) ($data['tags'] ?? []), $teamId, $this->userId(), $wasApproved);

            $this->handleReceiptUploads($request, (int) $id, $teamId);

            $this->respond($request, true, 'Expense updated.', null, "/expenses/$id");
        } catch (InvalidArgumentException $e) {
            $this->respond($request, false, $e->getMessage(), null, "/expenses/$id/edit");
        }
    }

    public function addReceipts(Request $request, string $id): void
    {
        $expense = $this->expenses->find((int) $id);
        if ($expense === null || (int) $expense['team_id'] !== $this->teamId()) {
            Response::abort(404);
        }
        if (!$this->can('receipt.upload')) {
            Response::abort(403);
        }
        $this->handleReceiptUploads($request, (int) $id, $this->teamId());
        $this->respond($request, true, 'Receipts uploaded.', null, "/expenses/$id");
    }

    public function submit(Request $request, string $id): void
    {
        $expense = $this->expenses->find((int) $id);
        if ($expense === null || (int) $expense['team_id'] !== $this->teamId()) {
            Response::abort(404);
        }
        if (!$this->can('expense.submit')) {
            Response::abort(403);
        }
        $this->service->submit((int) $id, $this->teamId(), $this->userId());
        $this->respond($request, true, 'Expense submitted for approval.', null, "/expenses/$id");
    }

    public function approve(Request $request, string $id): void
    {
        $expense = $this->expenses->find((int) $id);
        if ($expense === null || (int) $expense['team_id'] !== $this->teamId()) {
            Response::abort(404);
        }
        $settings = (new \App\Models\TeamSettings())->forTeam($this->teamId());
        if (!ExpensePolicy::canApprove($expense, $this->role(), $this->userId(), (bool) $settings['allow_self_approval'])) {
            Response::abort(403, 'You cannot approve this expense.');
        }
        $this->service->approve((int) $id, $this->teamId(), $this->userId(), (string) $request->input('notes', ''));

        $splits = $this->expenses->splits((int) $id);
        $recipients = array_unique(array_merge([(int) $expense['created_by']], array_map('intval', array_column($splits, 'user_id'))));
        (new NotificationService())->notifyMany($recipients, $this->teamId(), 'expense_approved', 'Expense approved', 'Your expense was approved.', "/expenses/$id");

        $this->respond($request, true, 'Expense approved.', null, "/expenses/$id");
    }

    public function reject(Request $request, string $id): void
    {
        $expense = $this->expenses->find((int) $id);
        if ($expense === null || (int) $expense['team_id'] !== $this->teamId()) {
            Response::abort(404);
        }
        if (!$this->can('expense.approve_reject')) {
            Response::abort(403);
        }
        $reason = trim((string) $request->input('reason', ''));
        if ($reason === '') {
            $this->respond($request, false, 'A rejection reason is required.', null, "/expenses/$id");
            return;
        }
        $this->service->reject((int) $id, $this->teamId(), $this->userId(), $reason);
        (new NotificationService())->notify((int) $expense['created_by'], $this->teamId(), 'expense_rejected', 'Expense rejected', "Rejected: $reason", "/expenses/$id");
        $this->respond($request, true, 'Expense rejected.', null, "/expenses/$id");
    }

    public function reimburse(Request $request, string $id): void
    {
        if (!$this->can('expense.reimburse')) {
            Response::abort(403);
        }
        $expense = $this->expenses->find((int) $id);
        if ($expense === null || (int) $expense['team_id'] !== $this->teamId() || $expense['status'] !== 'approved') {
            $this->respond($request, false, 'Only approved expenses can be marked reimbursed.', null, "/expenses/$id");
            return;
        }
        $this->service->reimburse((int) $id, $this->teamId(), $this->userId());
        $this->respond($request, true, 'Expense marked as reimbursed.', null, "/expenses/$id");
    }

    public function withdraw(Request $request, string $id): void
    {
        $expense = $this->expenses->find((int) $id);
        if ($expense === null || (int) $expense['team_id'] !== $this->teamId() || (int) $expense['created_by'] !== $this->userId()) {
            Response::abort(403);
        }
        $this->service->withdraw((int) $id, $this->teamId(), $this->userId());
        $this->respond($request, true, 'Expense withdrawn to draft.', null, "/expenses/$id");
    }

    public function delete(Request $request, string $id): void
    {
        $expense = $this->expenses->find((int) $id);
        if ($expense === null || (int) $expense['team_id'] !== $this->teamId()) {
            Response::abort(404);
        }
        if (!ExpensePolicy::canDelete($expense, $this->role(), $this->userId())) {
            Response::abort(403);
        }
        $this->service->softDelete((int) $id, $this->teamId(), $this->userId(), (string) $request->input('reason', ''));
        $this->respond($request, true, 'Expense deleted.', null, '/expenses');
    }

    public function previewSplit(Request $request): void
    {
        try {
            $totalCents = Money::toCents((string) $request->input('amount', '0'));
            $rows = $this->buildSplitsFromRequest($request, $totalCents);
            $formatted = array_map(fn($r) => ['user_id' => $r['user_id'], 'owed_amount' => Money::toDecimal($r['owed_cents'])], $rows);
            Response::success(['splits' => $formatted, 'total' => Money::toDecimal($totalCents)]);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage());
        }
    }

    public function exportCsv(Request $request): void
    {
        if (!$this->can('report.export')) {
            Response::abort(403);
        }
        $teamId = $this->teamId();
        $filters = $this->filtersFromRequest($request);
        [$visSql, $visParams] = ExpensePolicy::visibilitySql($this->role(), $this->userId());
        $result = $this->expenses->listForTeam($teamId, $filters, $visSql, $visParams, 1, 50000);

        $rows = array_map(function ($e) {
            return [
                'id' => $e['id'],
                'expense_date' => $e['expense_date'],
                'title' => $e['title'],
                'description' => $e['description'],
                'amount' => $e['amount'],
                'currency' => $e['currency'],
                'paid_by' => $e['payer_name'] ?? '',
                'created_by' => $e['creator_name'] ?? '',
                'category' => $e['category_name'] ?? '',
                'project' => $e['project_name'] ?? '',
                'split_type' => $e['split_type'],
                'status' => $e['status'],
                'receipt_count' => $e['receipt_count'] ?? 0,
                'created_at' => $e['created_at'],
                'updated_at' => $e['updated_at'],
            ];
        }, $result['rows']);

        (new CsvExportService())->stream('expenses.csv', [
            'id' => 'Expense ID', 'expense_date' => 'Expense Date', 'title' => 'Title', 'description' => 'Description',
            'amount' => 'Amount', 'currency' => 'Currency', 'paid_by' => 'Paid By', 'created_by' => 'Created By',
            'category' => 'Category', 'project' => 'Project', 'split_type' => 'Split Type', 'status' => 'Approval Status',
            'receipt_count' => 'Receipt Count', 'created_at' => 'Created At', 'updated_at' => 'Updated At',
        ], $rows);
    }
}
