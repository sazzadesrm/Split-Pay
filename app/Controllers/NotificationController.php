<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Notification;

final class NotificationController extends Controller
{
    public function index(Request $request): void
    {
        $notifications = new Notification();
        $page = max(1, (int) $request->query('page', 1));
        $filters = ['view' => $request->query('view', 'all'), 'team_id' => $request->query('team_id')];

        Response::view('notifications/index', [
            'title' => 'Notifications',
            'notifications' => $notifications->listForUser($this->userId(), $filters, $page, 20),
            'view' => $filters['view'],
        ]);
    }

    public function dropdown(Request $request): void
    {
        $notifications = new Notification();
        Response::success([
            'unread_count' => $notifications->unreadCount($this->userId()),
            'items' => $notifications->latestForUser($this->userId(), 8),
        ]);
    }

    public function markRead(Request $request, string $id): void
    {
        $notifications = new Notification();
        $notifications->markRead((int) $id, $this->userId());
        $this->respond($request, true, 'Marked as read.', null, '/notifications');
    }

    public function markAllRead(Request $request): void
    {
        $notifications = new Notification();
        $notifications->markAllRead($this->userId());
        $this->respond($request, true, 'All notifications marked as read.', null, '/notifications');
    }
}
