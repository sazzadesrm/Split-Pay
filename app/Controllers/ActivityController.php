<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\TeamMember;

final class ActivityController extends Controller
{
    public function index(Request $request): void
    {
        if (!$this->can('activity.view')) {
            Response::abort(403);
        }
        $logs = new ActivityLog();
        $page = max(1, (int) $request->query('page', 1));
        $filters = [
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'user_id' => $request->query('user_id'),
            'action' => $request->query('action'),
            'entity_type' => $request->query('entity_type'),
        ];
        Response::view('reports/activity', [
            'title' => 'Activity Log',
            'logs' => $logs->listForTeam($this->teamId(), $filters, $page, 30),
            'meta' => $this->paginationMeta($logs->countForTeam($this->teamId()), $page, 30),
            'members' => (new TeamMember())->listForTeam($this->teamId()),
            'filters' => $filters,
        ]);
    }
}
