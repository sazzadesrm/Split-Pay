<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Project;
use App\Models\Team;
use App\Services\BalanceService;

final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $teams = new Team();
        $userTeams = $teams->teamsForUser($this->userId());
        if (empty($userTeams)) {
            Response::redirect(url('/teams/create'));
        }
        if (\App\Core\Auth::activeTeamId() === null) {
            \App\Core\Auth::setActiveTeamId((int) $userTeams[0]['id']);
        }

        $teamId = $this->teamId();
        $balance = new BalanceService();

        $monthTotal = Database::connection()->prepare(
            "SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE team_id = :t AND status IN ('approved','reimbursed')
             AND deleted_at IS NULL AND YEAR(expense_date) = YEAR(CURDATE()) AND MONTH(expense_date) = MONTH(CURDATE())"
        );
        $monthTotal->execute(['t' => $teamId]);

        $pending = Database::connection()->prepare(
            "SELECT COALESCE(SUM(amount),0) t, COUNT(*) c FROM expenses WHERE team_id = :t AND status = 'submitted' AND deleted_at IS NULL"
        );
        $pending->execute(['t' => $teamId]);
        $pendingRow = $pending->fetch();

        $projects = new Project();

        Response::view('dashboard/index', [
            'title' => 'Dashboard',
            'teamId' => $teamId,
            'monthTotal' => $monthTotal->fetch()['t'],
            'pendingTotal' => $pendingRow['t'],
            'pendingCount' => $pendingRow['c'],
            'activeProjects' => $projects->activeCountForTeam($teamId),
            'yourBalance' => $balance->getUserBalance($teamId, $this->userId()),
            'role' => $this->role(),
        ]);
    }

    public function chartData(Request $request, string $chart): void
    {
        $teamId = $this->teamId();
        $pdo = Database::connection();

        if ($chart === 'monthly-trend') {
            $stmt = $pdo->prepare(
                "SELECT DATE_FORMAT(expense_date, '%Y-%m') ym, SUM(amount) total
                 FROM expenses WHERE team_id = :t AND status IN ('approved','reimbursed') AND deleted_at IS NULL
                 AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                 GROUP BY ym ORDER BY ym"
            );
            $stmt->execute(['t' => $teamId]);
            Response::success($stmt->fetchAll());
            return;
        }

        if ($chart === 'by-category') {
            $stmt = $pdo->prepare(
                "SELECT COALESCE(c.name,'Uncategorized') name, SUM(e.amount) total
                 FROM expenses e LEFT JOIN categories c ON c.id = e.category_id
                 WHERE e.team_id = :t AND e.status IN ('approved','reimbursed') AND e.deleted_at IS NULL
                 GROUP BY name ORDER BY total DESC"
            );
            $stmt->execute(['t' => $teamId]);
            Response::success($stmt->fetchAll());
            return;
        }

        Response::error('Unknown chart.', [], 404);
    }
}
