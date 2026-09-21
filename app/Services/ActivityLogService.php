<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;

final class ActivityLogService
{
    private ActivityLog $logs;

    public function __construct()
    {
        $this->logs = new ActivityLog();
    }

    public function log(int $teamId, ?int $userId, string $action, string $entityType, ?int $entityId, string $description, array $metadata = []): void
    {
        $this->logs->insert([
            'team_id' => $teamId,
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'metadata_json' => $metadata ? json_encode($metadata) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
