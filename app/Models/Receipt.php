<?php
declare(strict_types=1);

namespace App\Models;

final class Receipt extends Model
{
    protected string $table = 'expense_receipts';

    public function findWithExpense(int $id): ?array
    {
        return $this->queryOne(
            'SELECT r.*, e.team_id, e.status AS expense_status, e.created_by AS expense_created_by
             FROM expense_receipts r JOIN expenses e ON e.id = r.expense_id
             WHERE r.id = :id AND r.deleted_at IS NULL AND e.deleted_at IS NULL',
            ['id' => $id]
        );
    }

    public function countForExpense(int $expenseId): int
    {
        $row = $this->queryOne('SELECT COUNT(*) c FROM expense_receipts WHERE expense_id = :id AND deleted_at IS NULL', ['id' => $expenseId]);
        return (int) ($row['c'] ?? 0);
    }
}
