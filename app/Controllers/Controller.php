<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\TeamMember;

abstract class Controller
{
    protected function teamId(): int
    {
        return (int) Auth::activeTeamId();
    }

    protected function userId(): int
    {
        return (int) Auth::id();
    }

    protected function role(): string
    {
        $members = new TeamMember();
        $membership = $members->membership($this->teamId(), $this->userId());
        return $membership['role'] ?? 'viewer';
    }

    protected function can(string $permission): bool
    {
        $config = config('permissions');
        $allowed = $config['permissions'][$permission] ?? [];
        return in_array($this->role(), $allowed, true);
    }

    protected function redirectBack(string $fallback = '/dashboard'): void
    {
        Response::redirect(url($fallback));
    }

    protected function flashSuccess(string $message): void
    {
        Session::flash('success', $message);
    }

    protected function flashError(string $message): void
    {
        Session::flash('error', $message);
    }

    protected function respond(Request $request, bool $success, string $message, mixed $data = null, string $redirect = '/dashboard', int $errorStatus = 422): void
    {
        if ($request->isAjax()) {
            $success ? Response::success($data, $message) : Response::error($message, [], $errorStatus);
            return;
        }
        $success ? $this->flashSuccess($message) : $this->flashError($message);
        Response::redirect(url($redirect));
    }

    protected function paginationMeta(int $total, int $page, int $perPage): array
    {
        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ];
    }
}
