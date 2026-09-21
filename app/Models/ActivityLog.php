<?php
declare(strict_types=1);

namespace App\Models;

final class ActivityLog extends Model
{
    protected string $table = 'activity_logs';
    protected bool $softDeletes = false;

    public function listForTeam(int $teamId, array $filters = [], int $page = 1, int $perPage = 30): array
    {
        $where = 'WHERE al.team_id = :t';
        $params = ['t' => $teamId];
        if (!empty($filters['date_from'])) {
            $where .= ' AND al.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND al.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['user_id'])) {
            $where .= ' AND al.user_id = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where .= ' AND al.action LIKE :action';
            $params['action'] = $filters['action'] . '%';
        }
        if (!empty($filters['entity_type'])) {
            $where .= ' AND al.entity_type = :entity_type';
            $params['entity_type'] = $filters['entity_type'];
        }
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT al.*, u.name AS user_name FROM activity_logs al
                LEFT JOIN users u ON u.id = al.user_id
                $where ORDER BY al.created_at DESC LIMIT $perPage OFFSET $offset";
        return $this->query($sql, $params);
    }

    public function countForTeam(int $teamId): int
    {
        $row = $this->queryOne('SELECT COUNT(*) c FROM activity_logs WHERE team_id = :t', ['t' => $teamId]);
        return (int) ($row['c'] ?? 0);
    }
}
