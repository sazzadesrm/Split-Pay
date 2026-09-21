<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Category;
use App\Models\Project;
use App\Models\TeamMember;
use App\Services\BalanceService;

final class ReportController extends Controller
{
    public function index(Request $request): void
    {
        $teamId = $this->teamId();
        $pdo = Database::connection();

        $overview = $pdo->prepare(
            "SELECT
                SUM(CASE WHEN status IN ('approved','reimbursed') THEN amount ELSE 0 END) approved_total,
                SUM(CASE WHEN status = 'submitted' THEN amount ELSE 0 END) pending_total,
                SUM(CASE WHEN status = 'reimbursed' THEN amount ELSE 0 END) reimbursed_total,
                AVG(CASE WHEN status IN ('approved','reimbursed') THEN amount END) avg_expense,
                MAX(CASE WHEN status IN ('approved','reimbursed') THEN amount END) max_expense
             FROM expenses WHERE team_id = :t AND deleted_at IS NULL"
        );
        $overview->execute(['t' => $teamId]);

        $byCategory = $pdo->prepare(
            "SELECT COALESCE(c.name,'Uncategorized') name, SUM(e.amount) total, COUNT(*) cnt
             FROM expenses e LEFT JOIN categories c ON c.id = e.category_id
             WHERE e.team_id = :t AND e.status IN ('approved','reimbursed') AND e.deleted_at IS NULL
             GROUP BY name ORDER BY total DESC"
        );
        $byCategory->execute(['t' => $teamId]);

        $byProject = $pdo->prepare(
            "SELECT p.id, p.name, p.budget_amount, p.status,
                    COALESCE(SUM(CASE WHEN e.status IN ('approved','reimbursed') THEN e.amount END),0) spent
             FROM projects p LEFT JOIN expenses e ON e.project_id = p.id AND e.deleted_at IS NULL
             WHERE p.team_id = :t AND p.deleted_at IS NULL GROUP BY p.id ORDER BY p.name"
        );
        $byProject->execute(['t' => $teamId]);

        $approvalQueue = $pdo->prepare(
            "SELECT e.id, e.title, e.amount, e.expense_date, u.name submitter,
                    (SELECT COUNT(*) FROM expense_receipts r WHERE r.expense_id = e.id AND r.deleted_at IS NULL) receipts
             FROM expenses e JOIN users u ON u.id = e.created_by
             WHERE e.team_id = :t AND e.status = 'submitted' AND e.deleted_at IS NULL ORDER BY e.expense_date"
        );
        $approvalQueue->execute(['t' => $teamId]);

        Response::view('reports/index', [
            'title' => 'Reports',
            'overview' => $overview->fetch(),
            'byCategory' => $byCategory->fetchAll(),
            'byProject' => $byProject->fetchAll(),
            'approvalQueue' => $this->can('expense.approve_reject') ? $approvalQueue->fetchAll() : [],
            'memberBalances' => (new BalanceService())->getTeamMemberBalances($teamId),
            'suggestions' => (new BalanceService())->getSuggestedSettlements($teamId),
            'categories' => (new Category())->listForTeam($teamId, false),
            'projects' => (new Project())->listForTeam($teamId, [], 1, 100),
            'members' => (new TeamMember())->listForTeam($teamId),
            'role' => $this->role(),
        ]);
    }
}
