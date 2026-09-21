<?php
declare(strict_types=1);

namespace App\Models;

final class TeamMember extends Model
{
    protected string $table = 'team_members';
    protected bool $softDeletes = false;

    public function membership(int $teamId, int $userId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM team_members WHERE team_id = :t AND user_id = :u',
            ['t' => $teamId, 'u' => $userId]
        );
    }

    public function isActiveMember(int $teamId, int $userId): bool
    {
        $m = $this->membership($teamId, $userId);
        return $m !== null && (int) $m['is_active'] === 1;
    }

    public function listForTeam(int $teamId): array
    {
        return $this->query(
            'SELECT tm.*, u.name, u.email, u.avatar_path
             FROM team_members tm JOIN users u ON u.id = tm.user_id
             WHERE tm.team_id = :t ORDER BY FIELD(tm.role,"owner","admin","member","viewer"), u.name',
            ['t' => $teamId]
        );
    }

    public function activeMemberIds(int $teamId): array
    {
        $rows = $this->query('SELECT user_id FROM team_members WHERE team_id = :t AND is_active = 1', ['t' => $teamId]);
        return array_map('intval', array_column($rows, 'user_id'));
    }

    public function touchLastActive(int $teamId, int $userId): void
    {
        $this->execute(
            'UPDATE team_members SET last_active_at = UTC_TIMESTAMP() WHERE team_id = :t AND user_id = :u',
            ['t' => $teamId, 'u' => $userId]
        );
    }
}
