<?php
declare(strict_types=1);

namespace App\Models;

final class Expense extends Model
{
    protected string $table = 'expenses';

    private const SORT_WHITELIST = [
        'expense_date' => 'e.expense_date',
        'amount' => 'e.amount',
        'title' => 'e.title',
        'status' => 'e.status',
        'created_at' => 'e.created_at',
    ];

    public function findDetailed(int $id): ?array
    {
        return $this->queryOne(
            'SELECT e.*, c.name AS category_name, c.color AS category_color,
                    p.name AS project_name, payer.name AS payer_name,
                    creator.name AS creator_name, approver.name AS approver_name,
                    rejecter.name AS rejecter_name
             FROM expenses e
             LEFT JOIN categories c ON c.id = e.category_id
             LEFT JOIN projects p ON p.id = e.project_id
             JOIN users payer ON payer.id = e.paid_by_user_id
             JOIN users creator ON creator.id = e.created_by
             LEFT JOIN users approver ON approver.id = e.approved_by
             LEFT JOIN users rejecter ON rejecter.id = e.rejected_by
             WHERE e.id = :id AND e.deleted_at IS NULL',
            ['id' => $id]
        );
    }

    /**
     * @return array{rows: array, total: int}
     */
    public function listForTeam(int $teamId, array $filters, string $visibilitySql, array $visibilityParams, int $page = 1, int $perPage = 20): array
    {
        [$where, $params] = $this->buildFilters($teamId, $filters);
        $where .= $visibilitySql;
        $params = array_merge($params, $visibilityParams);

        $sortColumn = self::SORT_WHITELIST[$filters['sort'] ?? 'expense_date'] ?? 'e.expense_date';
        $direction = strtolower($filters['direction'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;
        $offset = max(0, ($page - 1) * $perPage);

        $countSql = "SELECT COUNT(DISTINCT e.id) c FROM expenses e
            LEFT JOIN categories c2 ON c2.id = e.category_id
            LEFT JOIN projects p ON p.id = e.project_id
            JOIN users payer ON payer.id = e.paid_by_user_id
            JOIN users creator ON creator.id = e.created_by
            LEFT JOIN expense_tags et ON et.expense_id = e.id
            LEFT JOIN tags tg ON tg.id = et.tag_id
            $where";
        $total = (int) ($this->queryOne($countSql, $params)['c'] ?? 0);

        $sql = "SELECT DISTINCT e.*, c2.name AS category_name, c2.color AS category_color,
                p.name AS project_name, payer.name AS payer_name, creator.name AS creator_name,
                (SELECT COUNT(*) FROM expense_receipts r WHERE r.expense_id = e.id AND r.deleted_at IS NULL) AS receipt_count
            FROM expenses e
            LEFT JOIN categories c2 ON c2.id = e.category_id
            LEFT JOIN projects p ON p.id = e.project_id
            JOIN users payer ON payer.id = e.paid_by_user_id
            JOIN users creator ON creator.id = e.created_by
            LEFT JOIN expense_tags et ON et.expense_id = e.id
            LEFT JOIN tags tg ON tg.id = et.tag_id
            $where
            ORDER BY $sortColumn $direction
            LIMIT $perPage OFFSET $offset";

        $rows = $this->query($sql, $params);
        return ['rows' => $rows, 'total' => $total];
    }

    private function buildFilters(int $teamId, array $filters): array
    {
        $where = 'WHERE e.team_id = :team_id AND e.deleted_at IS NULL';
        $params = ['team_id' => $teamId];

        if (!empty($filters['search']) && mb_strlen($filters['search']) >= 2) {
            $like = '%' . addcslashes($filters['search'], '%_\\') . '%';
            $where .= ' AND (e.title LIKE :search OR e.description LIKE :search OR c2.name LIKE :search
                OR tg.name LIKE :search OR p.name LIKE :search OR p.client_name LIKE :search
                OR payer.name LIKE :search OR creator.name LIKE :search)';
            $params['search'] = $like;
        }
        if (!empty($filters['date_from'])) {
            $where .= ' AND e.expense_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND e.expense_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        if (!empty($filters['category_id'])) {
            $where .= ' AND e.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }
        if (!empty($filters['tag_id'])) {
            $where .= ' AND et.tag_id = :tag_id';
            $params['tag_id'] = (int) $filters['tag_id'];
        }
        if (!empty($filters['project_id'])) {
            $where .= ' AND e.project_id = :project_id';
            $params['project_id'] = (int) $filters['project_id'];
        }
        if (!empty($filters['paid_by_user_id'])) {
            $where .= ' AND e.paid_by_user_id = :paid_by_user_id';
            $params['paid_by_user_id'] = (int) $filters['paid_by_user_id'];
        }
        if (!empty($filters['status'])) {
            $where .= ' AND e.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['has_receipt'])) {
            $where .= ' AND EXISTS (SELECT 1 FROM expense_receipts r WHERE r.expense_id = e.id AND r.deleted_at IS NULL)';
        }
        if (!empty($filters['min_amount'])) {
            $where .= ' AND e.amount >= :min_amount';
            $params['min_amount'] = $filters['min_amount'];
        }
        if (!empty($filters['max_amount'])) {
            $where .= ' AND e.amount <= :max_amount';
            $params['max_amount'] = $filters['max_amount'];
        }

        return [$where, $params];
    }

    public function splits(int $expenseId): array
    {
        return $this->query(
            'SELECT es.*, u.name AS user_name FROM expense_splits es
             JOIN users u ON u.id = es.user_id WHERE es.expense_id = :id ORDER BY u.name',
            ['id' => $expenseId]
        );
    }

    public function tags(int $expenseId): array
    {
        return $this->query(
            'SELECT t.* FROM tags t JOIN expense_tags et ON et.tag_id = t.id WHERE et.expense_id = :id ORDER BY t.name',
            ['id' => $expenseId]
        );
    }

    public function receipts(int $expenseId): array
    {
        return $this->query(
            'SELECT r.*, u.name AS uploader_name FROM expense_receipts r
             JOIN users u ON u.id = r.uploaded_by
             WHERE r.expense_id = :id AND r.deleted_at IS NULL ORDER BY r.created_at',
            ['id' => $expenseId]
        );
    }

    public function belongsToTeam(int $id, int $teamId): bool
    {
        $row = $this->queryOne('SELECT id FROM expenses WHERE id = :id AND team_id = :t AND deleted_at IS NULL', ['id' => $id, 't' => $teamId]);
        return $row !== null;
    }
}
