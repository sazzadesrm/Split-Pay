<?php
declare(strict_types=1);

namespace App\Policies;

/**
 * Centralized expense visibility rules (PRD section 3.3), reused by lists,
 * detail pages, receipts, reports and exports.
 */
final class ExpensePolicy
{
    /**
     * Returns a SQL fragment (to append to a WHERE clause) plus bound
     * parameters that restrict an expense query to what $role/$userId may see.
     *
     * @return array{0:string,1:array}
     */
    public static function visibilitySql(string $role, int $userId): array
    {
        if (in_array($role, ['owner', 'admin'], true)) {
            return ['', []];
        }

        if ($role === 'member') {
            return [
                " AND (e.status IN ('approved','reimbursed') OR e.created_by = :viewer_id
                    OR EXISTS (SELECT 1 FROM expense_splits vs WHERE vs.expense_id = e.id AND vs.user_id = :viewer_id2))",
                ['viewer_id' => $userId, 'viewer_id2' => $userId],
            ];
        }

        // viewer
        return [" AND e.status IN ('approved','reimbursed')", []];
    }

    public static function canView(array $expense, string $role, int $userId, bool $isParticipant): bool
    {
        if (in_array($role, ['owner', 'admin'], true)) {
            return true;
        }
        if (in_array($expense['status'], ['approved', 'reimbursed'], true)) {
            return true;
        }
        if ($role === 'member') {
            return (int) $expense['created_by'] === $userId || $isParticipant;
        }
        return false;
    }

    public static function canEdit(array $expense, string $role, int $userId): bool
    {
        $isOwnDraft = (int) $expense['created_by'] === $userId && in_array($expense['status'], ['draft', 'rejected'], true);
        if (in_array($role, ['owner', 'admin'], true)) {
            return true;
        }
        if ($role === 'member') {
            return $isOwnDraft;
        }
        return false;
    }

    public static function canDelete(array $expense, string $role, int $userId): bool
    {
        $isDraft = in_array($expense['status'], ['draft'], true);
        if (in_array($role, ['owner', 'admin'], true)) {
            return true;
        }
        if ($role === 'member') {
            return $isDraft && (int) $expense['created_by'] === $userId;
        }
        return false;
    }

    public static function canApprove(array $expense, string $role, int $userId, bool $selfApprovalAllowed): bool
    {
        if (!in_array($role, ['owner', 'admin'], true)) {
            return false;
        }
        if ((int) $expense['created_by'] === $userId && !$selfApprovalAllowed) {
            return false;
        }
        return $expense['status'] === 'submitted';
    }
}
