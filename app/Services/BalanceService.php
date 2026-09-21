<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Money;

final class BalanceService
{
    /**
     * Total paid (as payer) on approved/reimbursed, non-deleted expenses, in cents.
     */
    public function getTotalPaidByUser(int $teamId, int $userId): int
    {
        $row = Database::connection()->prepare(
            "SELECT COALESCE(SUM(amount),0) total FROM expenses
             WHERE team_id = :team_id AND paid_by_user_id = :user_id
             AND status IN ('approved','reimbursed') AND deleted_at IS NULL"
        );
        $row->execute(['team_id' => $teamId, 'user_id' => $userId]);
        return Money::toCents((string) $row->fetch()['total']);
    }

    /**
     * Total owed (as split participant) on approved/reimbursed, non-deleted expenses, in cents.
     */
    public function getTotalOwedByUser(int $teamId, int $userId): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(es.owed_amount),0) total FROM expense_splits es
             JOIN expenses e ON e.id = es.expense_id
             WHERE e.team_id = :team_id AND es.user_id = :user_id
             AND e.status IN ('approved','reimbursed') AND e.deleted_at IS NULL"
        );
        $stmt->execute(['team_id' => $teamId, 'user_id' => $userId]);
        return Money::toCents((string) $stmt->fetch()['total']);
    }

    /**
     * Settlement adjustment: payer's balance increases by amount paid (completed
     * settlements only), receiver's balance decreases by the same amount.
     */
    public function getSettlementAdjustment(int $teamId, int $userId): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(amount),0) total FROM settlements
             WHERE team_id = :team_id AND payer_id = :user_id
             AND status = 'completed' AND deleted_at IS NULL"
        );
        $stmt->execute(['team_id' => $teamId, 'user_id' => $userId]);
        $paid = Money::toCents((string) $stmt->fetch()['total']);

        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(amount),0) total FROM settlements
             WHERE team_id = :team_id AND receiver_id = :user_id
             AND status = 'completed' AND deleted_at IS NULL"
        );
        $stmt->execute(['team_id' => $teamId, 'user_id' => $userId]);
        $received = Money::toCents((string) $stmt->fetch()['total']);

        return $paid - $received;
    }

    public function getUserBalance(int $teamId, int $userId): array
    {
        $paid = $this->getTotalPaidByUser($teamId, $userId);
        $owed = $this->getTotalOwedByUser($teamId, $userId);
        $adjustment = $this->getSettlementAdjustment($teamId, $userId);
        $balance = $paid - $owed + $adjustment;

        return [
            'user_id' => $userId,
            'total_paid_cents' => $paid,
            'total_owed_cents' => $owed,
            'settlement_adjustment_cents' => $adjustment,
            'balance_cents' => $balance,
            'status' => $balance > 0 ? 'owed_money' : ($balance < 0 ? 'owes_money' : 'settled'),
        ];
    }

    /**
     * @return array<int,array> balances for every active team member
     */
    public function getTeamMemberBalances(int $teamId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT tm.user_id, u.name, u.email, tm.role, tm.is_active
             FROM team_members tm JOIN users u ON u.id = tm.user_id
             WHERE tm.team_id = :team_id ORDER BY u.name"
        );
        $stmt->execute(['team_id' => $teamId]);
        $members = $stmt->fetchAll();

        $balances = [];
        foreach ($members as $member) {
            $balance = $this->getUserBalance($teamId, (int) $member['user_id']);
            $balances[] = array_merge($member, $balance);
        }
        return $balances;
    }

    public function getTeamOutstandingTotal(int $teamId): int
    {
        $balances = $this->getTeamMemberBalances($teamId);
        $total = 0;
        foreach ($balances as $b) {
            if ($b['balance_cents'] < 0) {
                $total += abs($b['balance_cents']);
            }
        }
        return $total;
    }

    /**
     * Suggested settlements: separate creditors (positive balance) from
     * debtors (negative), then greedily match debtors to creditors.
     * @return array<int,array{from_user_id:int, from_name:string, to_user_id:int, to_name:string, amount_cents:int}>
     */
    public function getSuggestedSettlements(int $teamId): array
    {
        $balances = $this->getTeamMemberBalances($teamId);

        $creditors = [];
        $debtors = [];
        foreach ($balances as $b) {
            if ($b['balance_cents'] > 0) {
                $creditors[] = ['user_id' => (int) $b['user_id'], 'name' => $b['name'], 'amount' => $b['balance_cents']];
            } elseif ($b['balance_cents'] < 0) {
                $debtors[] = ['user_id' => (int) $b['user_id'], 'name' => $b['name'], 'amount' => abs($b['balance_cents'])];
            }
        }

        $suggestions = [];
        $i = 0;
        $j = 0;
        while ($i < count($debtors) && $j < count($creditors)) {
            $amount = min($debtors[$i]['amount'], $creditors[$j]['amount']);
            if ($amount > 0) {
                $suggestions[] = [
                    'from_user_id' => $debtors[$i]['user_id'],
                    'from_name' => $debtors[$i]['name'],
                    'to_user_id' => $creditors[$j]['user_id'],
                    'to_name' => $creditors[$j]['name'],
                    'amount_cents' => $amount,
                ];
            }
            $debtors[$i]['amount'] -= $amount;
            $creditors[$j]['amount'] -= $amount;
            if ($debtors[$i]['amount'] === 0) {
                $i++;
            }
            if ($creditors[$j]['amount'] === 0) {
                $j++;
            }
        }

        return $suggestions;
    }
}
