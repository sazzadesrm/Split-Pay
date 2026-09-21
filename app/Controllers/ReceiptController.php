<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Receipt;
use App\Policies\ExpensePolicy;
use App\Services\ReceiptUploadService;

final class ReceiptController extends Controller
{
    public function view(Request $request, string $id): void
    {
        $this->serve($id, false);
    }

    public function download(Request $request, string $id): void
    {
        $this->serve($id, true);
    }

    private function serve(string $id, bool $download): void
    {
        $receipts = new Receipt();
        $receipt = $receipts->findWithExpense((int) $id);
        if ($receipt === null || (int) $receipt['team_id'] !== $this->teamId()) {
            Response::abort(404);
        }

        $expenses = new \App\Models\Expense();
        $splits = $expenses->splits((int) $receipt['expense_id']);
        $isParticipant = in_array($this->userId(), array_map('intval', array_column($splits, 'user_id')), true);
        $expense = ['status' => $receipt['expense_status'], 'created_by' => $receipt['expense_created_by']];

        if (!ExpensePolicy::canView($expense, $this->role(), $this->userId(), $isParticipant)) {
            Response::abort(403);
        }
        if ($this->role() === 'viewer') {
            Response::abort(403, 'Receipts are not available to viewers.');
        }

        $uploader = new ReceiptUploadService();
        $path = $uploader->resolveAbsolutePath($receipt['file_path']);
        if ($path === null || !is_file($path)) {
            Response::abort(404, 'Receipt file not found.');
        }

        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . $receipt['mime_type']);
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . basename($receipt['original_filename']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function delete(Request $request, string $id): void
    {
        $receipts = new Receipt();
        $receipt = $receipts->findWithExpense((int) $id);
        if ($receipt === null || (int) $receipt['team_id'] !== $this->teamId()) {
            Response::abort(404);
        }
        $ownExpense = (int) $receipt['expense_created_by'] === $this->userId();
        if (!$this->can('receipt.delete_any') && !($this->can('receipt.upload') && $ownExpense)) {
            Response::abort(403);
        }
        (new ReceiptUploadService())->delete((int) $id, $this->teamId(), $this->userId());
        $this->respond($request, true, 'Receipt deleted.', null, '/expenses/' . $receipt['expense_id']);
    }
}
