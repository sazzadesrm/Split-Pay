<?php
declare(strict_types=1);

namespace App\Models;

final class TeamInvitation extends Model
{
    protected string $table = 'team_invitations';
    protected bool $softDeletes = false;

    public function findByToken(string $token): ?array
    {
        return $this->queryOne('SELECT * FROM team_invitations WHERE token = :t', ['t' => $token]);
    }

    public function listForTeam(int $teamId): array
    {
        return $this->query(
            'SELECT * FROM team_invitations WHERE team_id = :t ORDER BY created_at DESC',
            ['t' => $teamId]
        );
    }
}
