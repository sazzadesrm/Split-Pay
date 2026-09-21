<?php
declare(strict_types=1);

namespace App\Models;

final class Tag extends Model
{
    protected string $table = 'tags';
    protected bool $softDeletes = false;

    public static function normalize(string $name): string
    {
        $name = trim(mb_strtolower($name));
        $name = preg_replace('/\s+/', '-', $name);
        return mb_substr($name, 0, 80);
    }

    public function listForTeam(int $teamId): array
    {
        return $this->query('SELECT * FROM tags WHERE team_id = :t ORDER BY name', ['t' => $teamId]);
    }

    public function findByName(int $teamId, string $name): ?array
    {
        return $this->queryOne('SELECT * FROM tags WHERE team_id = :t AND name = :n', ['t' => $teamId, 'n' => $name]);
    }

    public function isInUse(int $tagId): bool
    {
        $row = $this->queryOne('SELECT expense_id FROM expense_tags WHERE tag_id = :id LIMIT 1', ['id' => $tagId]);
        return $row !== null;
    }

    public function belongsToTeam(int $id, int $teamId): bool
    {
        $row = $this->queryOne('SELECT id FROM tags WHERE id = :id AND team_id = :t', ['id' => $id, 't' => $teamId]);
        return $row !== null;
    }
}
