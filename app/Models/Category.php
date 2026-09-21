<?php
declare(strict_types=1);

namespace App\Models;

final class Category extends Model
{
    protected string $table = 'categories';
    protected bool $softDeletes = false;

    public function listForTeam(int $teamId, bool $includeArchived = true): array
    {
        $sql = 'SELECT * FROM categories WHERE (team_id = :t OR team_id IS NULL)';
        if (!$includeArchived) {
            $sql .= ' AND is_archived = 0';
        }
        $sql .= ' ORDER BY sort_order, name';
        return $this->query($sql, ['t' => $teamId]);
    }

    public function belongsToTeam(int $id, int $teamId): bool
    {
        $row = $this->queryOne('SELECT id FROM categories WHERE id = :id AND (team_id = :t OR team_id IS NULL)', ['id' => $id, 't' => $teamId]);
        return $row !== null;
    }
}
