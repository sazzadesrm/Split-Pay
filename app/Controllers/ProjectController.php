<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Project;
use App\Services\ActivityLogService;
use App\Services\CsvExportService;

final class ProjectController extends Controller
{
    public function index(Request $request): void
    {
        $projects = new Project();
        $teamId = $this->teamId();
        $page = max(1, (int) $request->query('page', 1));
        $filters = ['status' => $request->query('status')];

        $list = $projects->listForTeam($teamId, $filters, $page, 12);
        foreach ($list as &$p) {
            $p['spent'] = $this->approvedSpend((int) $p['id']);
            $p['percent_used'] = $p['budget_amount'] > 0 ? min(999, round(($p['spent'] / (float) $p['budget_amount']) * 100)) : 0;
        }
        unset($p);

        Response::view('projects/index', [
            'title' => 'Projects',
            'projects' => $list,
            'meta' => $this->paginationMeta($projects->countForTeam($teamId, $filters), $page, 12),
            'role' => $this->role(),
        ]);
    }

    private function approvedSpend(int $projectId): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE project_id = :p AND status IN ('approved','reimbursed') AND deleted_at IS NULL"
        );
        $stmt->execute(['p' => $projectId]);
        return (float) $stmt->fetch()['t'];
    }

    public function showCreate(Request $request): void
    {
        if (!$this->can('project.manage')) {
            Response::abort(403);
        }
        Response::view('projects/form', ['title' => 'New Project', 'project' => null]);
    }

    public function store(Request $request): void
    {
        if (!$this->can('project.manage')) {
            Response::abort(403);
        }
        $data = $request->all();
        $validator = new \App\Core\Validator($data);
        $validator->required('name', 'Project name')->string('name', 'Project name', 1, 150);
        if ($validator->fails()) {
            $this->respond($request, false, 'Please correct the errors below.', ['errors' => $validator->errors()], '/projects/create');
            return;
        }

        $projects = new Project();
        $id = $projects->insert([
            'team_id' => $this->teamId(),
            'name' => trim($data['name']),
            'client_name' => $data['client_name'] ?? null,
            'project_code' => $data['project_code'] ?? null,
            'description' => $data['description'] ?? null,
            'budget_amount' => $data['budget_amount'] ?: null,
            'currency' => $data['currency'] ?? 'USD',
            'start_date' => $data['start_date'] ?: null,
            'end_date' => $data['end_date'] ?: null,
            'status' => $data['status'] ?? 'active',
            'created_by' => $this->userId(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        (new ActivityLogService())->log($this->teamId(), $this->userId(), 'project.created', 'project', $id, "Project \"{$data['name']}\" created.");
        $this->respond($request, true, 'Project created.', ['id' => $id], "/projects/$id");
    }

    public function show(Request $request, string $id): void
    {
        $projects = new Project();
        $project = $projects->find((int) $id);
        if ($project === null || (int) $project['team_id'] !== $this->teamId()) {
            Response::abort(404);
        }

        $expenses = Database::connection()->prepare(
            "SELECT e.*, c.name AS category_name, payer.name AS payer_name FROM expenses e
             LEFT JOIN categories c ON c.id = e.category_id JOIN users payer ON payer.id = e.paid_by_user_id
             WHERE e.project_id = :p AND e.deleted_at IS NULL ORDER BY e.expense_date DESC"
        );
        $expenses->execute(['p' => $id]);
        $expenseRows = $expenses->fetchAll();

        $categoryTotals = Database::connection()->prepare(
            "SELECT COALESCE(c.name,'Uncategorized') name, SUM(e.amount) total FROM expenses e
             LEFT JOIN categories c ON c.id = e.category_id
             WHERE e.project_id = :p AND e.status IN ('approved','reimbursed') AND e.deleted_at IS NULL GROUP BY name"
        );
        $categoryTotals->execute(['p' => $id]);

        $memberTotals = Database::connection()->prepare(
            "SELECT payer.name, SUM(e.amount) total FROM expenses e JOIN users payer ON payer.id = e.paid_by_user_id
             WHERE e.project_id = :p AND e.status IN ('approved','reimbursed') AND e.deleted_at IS NULL GROUP BY payer.name"
        );
        $memberTotals->execute(['p' => $id]);

        $spent = $this->approvedSpend((int) $id);

        Response::view('projects/show', [
            'title' => $project['name'],
            'project' => $project,
            'expenses' => $expenseRows,
            'categoryTotals' => $categoryTotals->fetchAll(),
            'memberTotals' => $memberTotals->fetchAll(),
            'spent' => $spent,
            'percentUsed' => $project['budget_amount'] > 0 ? round(($spent / (float) $project['budget_amount']) * 100) : 0,
            'role' => $this->role(),
        ]);
    }

    public function showEdit(Request $request, string $id): void
    {
        if (!$this->can('project.manage')) {
            Response::abort(403);
        }
        $projects = new Project();
        $project = $projects->find((int) $id);
        if ($project === null || (int) $project['team_id'] !== $this->teamId()) {
            Response::abort(404);
        }
        Response::view('projects/form', ['title' => 'Edit Project', 'project' => $project]);
    }

    public function update(Request $request, string $id): void
    {
        if (!$this->can('project.manage')) {
            Response::abort(403);
        }
        $projects = new Project();
        $project = $projects->find((int) $id);
        if ($project === null || (int) $project['team_id'] !== $this->teamId()) {
            Response::abort(404);
        }
        $data = $request->all();
        $projects->update((int) $id, [
            'name' => trim($data['name']),
            'client_name' => $data['client_name'] ?? null,
            'project_code' => $data['project_code'] ?? null,
            'description' => $data['description'] ?? null,
            'budget_amount' => $data['budget_amount'] ?: null,
            'start_date' => $data['start_date'] ?: null,
            'end_date' => $data['end_date'] ?: null,
            'status' => $data['status'] ?? $project['status'],
        ]);
        (new ActivityLogService())->log($this->teamId(), $this->userId(), 'project.updated', 'project', (int) $id, 'Project updated.');
        $this->respond($request, true, 'Project updated.', null, "/projects/$id");
    }

    public function delete(Request $request, string $id): void
    {
        if (!$this->can('project.manage')) {
            Response::abort(403);
        }
        $projects = new Project();
        $projects->update((int) $id, ['status' => 'archived']);
        (new ActivityLogService())->log($this->teamId(), $this->userId(), 'project.archived', 'project', (int) $id, 'Project archived.');
        $this->respond($request, true, 'Project archived.', null, '/projects');
    }

    public function exportCsv(Request $request, string $id): void
    {
        if (!$this->can('report.export')) {
            Response::abort(403);
        }
        $stmt = Database::connection()->prepare(
            "SELECT e.id, e.expense_date, e.title, e.amount, e.currency, e.status, payer.name payer, c.name category
             FROM expenses e JOIN users payer ON payer.id = e.paid_by_user_id
             LEFT JOIN categories c ON c.id = e.category_id
             WHERE e.project_id = :p AND e.deleted_at IS NULL ORDER BY e.expense_date DESC"
        );
        $stmt->execute(['p' => $id]);

        (new CsvExportService())->stream('project-' . $id . '-expenses.csv', [
            'id' => 'Expense ID', 'expense_date' => 'Date', 'title' => 'Title', 'amount' => 'Amount',
            'currency' => 'Currency', 'status' => 'Status', 'payer' => 'Paid By', 'category' => 'Category',
        ], $stmt->fetchAll());
    }
}
