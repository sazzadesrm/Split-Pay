<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;

final class NotificationService
{
    private Notification $notifications;

    public function __construct()
    {
        $this->notifications = new Notification();
    }

    public function notify(int $userId, ?int $teamId, string $type, string $title, string $message, ?string $linkUrl = null): void
    {
        $this->notifications->insert([
            'user_id' => $userId,
            'team_id' => $teamId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link_url' => $linkUrl,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function notifyMany(array $userIds, ?int $teamId, string $type, string $title, string $message, ?string $linkUrl = null): void
    {
        foreach (array_unique($userIds) as $userId) {
            $this->notify($userId, $teamId, $type, $title, $message, $linkUrl);
        }
    }
}
