<?php
declare(strict_types=1);

namespace App\Models;

final class TeamSettings extends Model
{
    protected string $table = 'team_settings';
    protected bool $softDeletes = false;

    public function forTeam(int $teamId): array
    {
        $row = $this->queryOne('SELECT * FROM team_settings WHERE team_id = :t', ['t' => $teamId]);
        return $row ?? [
            'team_id' => $teamId,
            'approval_required' => 1,
            'allow_self_approval' => 0,
            'allow_member_settlements' => 1,
            'allow_member_csv_exports' => 1,
            'participant_visibility' => 1,
        ];
    }
}
