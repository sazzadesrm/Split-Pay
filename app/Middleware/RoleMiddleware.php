<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\TeamMember;

/**
 * Verifies the current user's role in the active team grants a given
 * permission, as defined in config/permissions.php.
 *
 * Usage: [RoleMiddleware::class, 'expense.approve_reject']
 */
final class RoleMiddleware
{
    public function __construct(private string $permission)
    {
    }

    public function handle(Request $request): bool
    {
        $teamId = Auth::activeTeamId();
        $members = new TeamMember();
        $membership = $members->membership((int) $teamId, (int) Auth::id());

        $config = config('permissions');
        $allowedRoles = $config['permissions'][$this->permission] ?? [];

        if ($membership === null || !in_array($membership['role'], $allowedRoles, true)) {
            \App\Core\Logger::security("Blocked permission '{$this->permission}' for user " . Auth::id());
            if ($request->isAjax()) {
                Response::error('You do not have permission to perform this action.', [], 403);
            }
            Response::abort(403, 'You do not have permission to perform this action.');
        }
        return true;
    }
}
