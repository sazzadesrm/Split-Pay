<?php
declare(strict_types=1);

namespace App\Models;

final class Project extends Model
{
    protected string $table = 'projects';

    public function listForTeam(int $teamId, array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $where = 'WHERE team_id = :t AND deleted_at IS NULL';
        $params = ['t' => $teamId];
        if (!empty($filters['status'])) {
            $where .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM projects $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
        return $this->query($sql, $params);
    }

    public function countForTeam(int $teamId, array $filters = []): int
    {
        $where = 'WHERE team_id = :t AND deleted_at IS NULL';
        $params = ['t' => $teamId];
        if (!empty($filters['status'])) {
            $where .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }
        $row = $this->queryOne("SELECT COUNT(*) c FROM projects $where", $params);
        return (int) ($row['c'] ?? 0);
    }

    public function belongsToTeam(int $id, int $teamId): bool
    {
        $row = $this->queryOne('SELECT id FROM projects WHERE id = :id AND team_id = :t AND deleted_at IS NULL', ['id' => $id, 't' => $teamId]);
        return $row !== null;
    }

    public function activeCountForTeam(int $teamId): int
    {
        $row = $this->queryOne("SELECT COUNT(*) c FROM projects WHERE team_id = :t AND status = 'active' AND deleted_at IS NULL", ['t' => $teamId]);
        return (int) ($row['c'] ?? 0);
    }
}
