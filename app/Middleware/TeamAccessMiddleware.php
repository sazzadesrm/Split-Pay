<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\TeamMember;

/**
 * Ensures the current user belongs to (and is active in) the active team.
 * Every team-scoped controller must run this middleware.
 */
final class TeamAccessMiddleware
{
    public function handle(Request $request): bool
    {
        $teamId = Auth::activeTeamId();
        if ($teamId === null) {
            Response::redirect(url('/teams'));
        }

        $members = new TeamMember();
        if (!$members->isActiveMember((int) $teamId, (int) Auth::id())) {
            \App\Core\Logger::security('Blocked access to team ' . $teamId . ' for user ' . Auth::id());
            if ($request->isAjax()) {
                Response::error('Not authorized.', [], 403);
            }
            Response::abort(403, 'You do not have access to this team.');
        }
        return true;
    }
}
