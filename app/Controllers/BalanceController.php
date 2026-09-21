<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\BalanceService;
use App\Services\CsvExportService;

final class BalanceController extends Controller
{
    public function index(Request $request): void
    {
        $teamId = $this->teamId();
        $balanceService = new BalanceService();

        Response::view('balances/index', [
            'title' => 'Balances & Settle Up',
            'yourBalance' => $balanceService->getUserBalance($teamId, $this->userId()),
            'teamOutstanding' => $balanceService->getTeamOutstandingTotal($teamId),
            'memberBalances' => $balanceService->getTeamMemberBalances($teamId),
            'suggestions' => $balanceService->getSuggestedSettlements($teamId),
            'role' => $this->role(),
        ]);
    }

    public function exportCsv(Request $request): void
    {
        if (!$this->can('report.export')) {
            Response::abort(403);
        }
        $balances = (new BalanceService())->getTeamMemberBalances($this->teamId());
        $rows = array_map(fn($b) => [
            'member' => $b['name'],
            'email' => $b['email'],
            'role' => $b['role'],
            'total_paid' => \App\Core\Money::toDecimal($b['total_paid_cents']),
            'total_owed' => \App\Core\Money::toDecimal($b['total_owed_cents']),
            'settlement_adjustment' => \App\Core\Money::toDecimal($b['settlement_adjustment_cents']),
            'balance' => \App\Core\Money::toDecimal($b['balance_cents']),
            'status' => $b['status'],
        ], $balances);

        (new CsvExportService())->stream('balances.csv', [
            'member' => 'Member', 'email' => 'Email', 'role' => 'Role', 'total_paid' => 'Total Paid',
            'total_owed' => 'Total Owed', 'settlement_adjustment' => 'Settlement Adjustment',
            'balance' => 'Current Balance', 'status' => 'Balance Status',
        ], $rows);
    }
}
