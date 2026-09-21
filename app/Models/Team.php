<?php
declare(strict_types=1);

namespace App\Models;

final class Team extends Model
{
    protected string $table = 'teams';

    public function teamsForUser(int $userId): array
    {
        return $this->query(
            'SELECT t.*, tm.role FROM teams t
             JOIN team_members tm ON tm.team_id = t.id
             WHERE tm.user_id = :u AND tm.is_active = 1 AND t.deleted_at IS NULL
             ORDER BY t.name',
            ['u' => $userId]
        );
    }
}
