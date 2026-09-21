<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Settlement;
use App\Models\TeamMember;
use App\Services\BalanceService;
use App\Services\CsvExportService;
use App\Services\SettlementService;
use InvalidArgumentException;
use RuntimeException;

final class SettlementController extends Controller
{
    public function index(Request $request): void
    {
        $teamId = $this->teamId();
        $page = max(1, (int) $request->query('page', 1));
        $filters = [
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'payer_id' => $request->query('payer_id'),
            'receiver_id' => $request->query('receiver_id'),
            'status' => $request->query('status'),
        ];
        $settlements = new Settlement();

        Response::view('settlements/index', [
            'title' => 'Settlements',
            'settlements' => $settlements->listForTeam($teamId, $filters, $page, 20),
            'meta' => $this->paginationMeta($settlements->countForTeam($teamId, $filters), $page, 20),
            'members' => (new TeamMember())->listForTeam($teamId),
            'suggestions' => (new BalanceService())->getSuggestedSettlements($teamId),
            'role' => $this->role(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): void
    {
        if (!$this->can('settlement.record')) {
            Response::abort(403);
        }
        $teamId = $this->teamId();
        $data = $request->all();

        if (!$this->can('settlement.manage_any')) {
            if ((int) ($data['payer_id'] ?? 0) !== $this->userId() && (int) ($data['receiver_id'] ?? 0) !== $this->userId()) {
                Response::abort(403, 'You may only record settlements you are involved in.');
            }
        }

        $members = new TeamMember();
        if (!$members->isActiveMember($teamId, (int) ($data['payer_id'] ?? 0)) || !$members->isActiveMember($teamId, (int) ($data['receiver_id'] ?? 0))) {
            $this->respond($request, false, 'Payer and receiver must be active team members.', null, '/settlements');
            return;
        }

        $balanceService = new BalanceService();
        $outstanding = $balanceService->getUserBalance($teamId, (int) $data['payer_id']);
        $amount = (float) ($data['amount'] ?? 0);
        $overpay = $amount * 100 > abs(min(0, $outstanding['balance_cents']));

        try {
            $id = (new SettlementService())->create([
                'payer_id' => (int) $data['payer_id'],
                'receiver_id' => (int) $data['receiver_id'],
                'amount' => (string) $data['amount'],
                'currency' => $data['currency'] ?? 'USD',
                'payment_method' => $data['payment_method'] ?? 'other',
                'reference_note' => $data['reference_note'] ?? null,
                'settlement_date' => $data['settlement_date'] ?? date('Y-m-d'),
                'status' => (string) ($data['status'] ?? 'pending'),
            ], $teamId, $this->userId());

            $message = 'Settlement recorded.' . ($overpay ? ' Note: this amount exceeds the current outstanding balance.' : '');
            $this->respond($request, true, $message, ['id' => $id, 'overpay_warning' => $overpay], '/settlements');
        } catch (InvalidArgumentException $e) {
            $this->respond($request, false, $e->getMessage(), null, '/settlements');
        }
    }

    public function complete(Request $request, string $id): void
    {
        $this->guardInvolvement($id);
        try {
            (new SettlementService())->complete((int) $id, $this->teamId(), $this->userId());
            $this->respond($request, true, 'Settlement marked as completed.', null, '/settlements');
        } catch (RuntimeException $e) {
            $this->respond($request, false, $e->getMessage(), null, '/settlements');
        }
    }

    public function cancel(Request $request, string $id): void
    {
        $this->guardInvolvement($id);
        try {
            (new SettlementService())->cancel((int) $id, $this->teamId(), $this->userId());
            $this->respond($request, true, 'Settlement cancelled.', null, '/settlements');
        } catch (RuntimeException $e) {
            $this->respond($request, false, $e->getMessage(), null, '/settlements');
        }
    }

    public function delete(Request $request, string $id): void
    {
        if (!$this->can('settlement.manage_any')) {
            Response::abort(403);
        }
        try {
            (new SettlementService())->delete((int) $id, $this->teamId(), $this->userId());
            $this->respond($request, true, 'Settlement deleted.', null, '/settlements');
        } catch (RuntimeException $e) {
            $this->respond($request, false, $e->getMessage(), null, '/settlements');
        }
    }

    private function guardInvolvement(string $id): void
    {
        $settlements = new Settlement();
        $settlement = $settlements->find((int) $id);
        if ($settlement === null || (int) $settlement['team_id'] !== $this->teamId()) {
            Response::abort(404);
        }
        if ($this->can('settlement.manage_any')) {
            return;
        }
        $involved = in_array($this->userId(), [(int) $settlement['payer_id'], (int) $settlement['receiver_id']], true);
        if (!$involved || !$this->can('settlement.record')) {
            Response::abort(403);
        }
    }

    public function exportCsv(Request $request): void
    {
        if (!$this->can('report.export')) {
            Response::abort(403);
        }
        $settlements = new Settlement();
        $rows = $settlements->listForTeam($this->teamId(), [], 1, 50000);
        $mapped = array_map(fn($s) => [
            'id' => $s['id'], 'date' => $s['settlement_date'], 'payer' => $s['payer_name'], 'receiver' => $s['receiver_name'],
            'amount' => $s['amount'], 'currency' => $s['currency'], 'payment_method' => $s['payment_method'],
            'reference_note' => $s['reference_note'], 'status' => $s['status'], 'completed_at' => $s['completed_at'],
            'created_by' => $s['creator_name'], 'created_at' => $s['created_at'],
        ], $rows);

        (new CsvExportService())->stream('settlements.csv', [
            'id' => 'Settlement ID', 'date' => 'Date', 'payer' => 'Payer', 'receiver' => 'Receiver', 'amount' => 'Amount',
            'currency' => 'Currency', 'payment_method' => 'Payment Method', 'reference_note' => 'Reference Note',
            'status' => 'Status', 'completed_at' => 'Completed At', 'created_by' => 'Created By', 'created_at' => 'Created At',
        ], $mapped);
    }
}
