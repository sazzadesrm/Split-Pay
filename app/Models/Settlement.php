<?php
declare(strict_types=1);

namespace App\Models;

final class Settlement extends Model
{
    protected string $table = 'settlements';

    public function listForTeam(int $teamId, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        [$where, $params] = $this->buildFilters($teamId, $filters);
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT s.*, payer.name AS payer_name, receiver.name AS receiver_name, creator.name AS creator_name
            FROM settlements s
            JOIN users payer ON payer.id = s.payer_id
            JOIN users receiver ON receiver.id = s.receiver_id
            JOIN users creator ON creator.id = s.created_by
            $where ORDER BY s.settlement_date DESC, s.id DESC LIMIT $perPage OFFSET $offset";
        return $this->query($sql, $params);
    }

    public function countForTeam(int $teamId, array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($teamId, $filters);
        $sql = "SELECT COUNT(*) c FROM settlements s
            JOIN users payer ON payer.id = s.payer_id
            JOIN users receiver ON receiver.id = s.receiver_id
            JOIN users creator ON creator.id = s.created_by $where";
        return (int) ($this->queryOne($sql, $params)['c'] ?? 0);
    }

    private function buildFilters(int $teamId, array $filters): array
    {
        $where = 'WHERE s.team_id = :t AND s.deleted_at IS NULL';
        $params = ['t' => $teamId];
        if (!empty($filters['date_from'])) {
            $where .= ' AND s.settlement_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND s.settlement_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        if (!empty($filters['payer_id'])) {
            $where .= ' AND s.payer_id = :payer_id';
            $params['payer_id'] = (int) $filters['payer_id'];
        }
        if (!empty($filters['receiver_id'])) {
            $where .= ' AND s.receiver_id = :receiver_id';
            $params['receiver_id'] = (int) $filters['receiver_id'];
        }
        if (!empty($filters['status'])) {
            $where .= ' AND s.status = :status';
            $params['status'] = $filters['status'];
        }
        return [$where, $params];
    }

    public function findDetailed(int $id): ?array
    {
        return $this->queryOne(
            'SELECT s.*, payer.name AS payer_name, receiver.name AS receiver_name
             FROM settlements s JOIN users payer ON payer.id = s.payer_id
             JOIN users receiver ON receiver.id = s.receiver_id
             WHERE s.id = :id AND s.deleted_at IS NULL',
            ['id' => $id]
        );
    }

    public function belongsToTeam(int $id, int $teamId): bool
    {
        $row = $this->queryOne('SELECT id FROM settlements WHERE id = :id AND team_id = :t AND deleted_at IS NULL', ['id' => $id, 't' => $teamId]);
        return $row !== null;
    }
}
