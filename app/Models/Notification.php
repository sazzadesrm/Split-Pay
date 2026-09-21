<?php
declare(strict_types=1);

namespace App\Models;

final class Notification extends Model
{
    protected string $table = 'notifications';
    protected bool $softDeletes = false;

    public function latestForUser(int $userId, int $limit = 10): array
    {
        return $this->query(
            'SELECT * FROM notifications WHERE user_id = :u ORDER BY created_at DESC LIMIT ' . max(1, $limit),
            ['u' => $userId]
        );
    }

    public function unreadCount(int $userId): int
    {
        $row = $this->queryOne('SELECT COUNT(*) c FROM notifications WHERE user_id = :u AND is_read = 0', ['u' => $userId]);
        return (int) ($row['c'] ?? 0);
    }

    public function listForUser(int $userId, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = 'WHERE user_id = :u';
        $params = ['u' => $userId];
        if (($filters['view'] ?? 'all') === 'unread') {
            $where .= ' AND is_read = 0';
        } elseif (($filters['view'] ?? '') === 'read') {
            $where .= ' AND is_read = 1';
        }
        if (!empty($filters['team_id'])) {
            $where .= ' AND team_id = :team_id';
            $params['team_id'] = (int) $filters['team_id'];
        }
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM notifications $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
        return $this->query($sql, $params);
    }

    public function markRead(int $id, int $userId): bool
    {
        return $this->execute(
            'UPDATE notifications SET is_read = 1, read_at = UTC_TIMESTAMP() WHERE id = :id AND user_id = :u',
            ['id' => $id, 'u' => $userId]
        );
    }

    public function markAllRead(int $userId): bool
    {
        return $this->execute(
            'UPDATE notifications SET is_read = 1, read_at = UTC_TIMESTAMP() WHERE user_id = :u AND is_read = 0',
            ['u' => $userId]
        );
    }
}
