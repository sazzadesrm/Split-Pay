<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

final class AuthMiddleware
{
    public function handle(Request $request): bool
    {
        if (!Auth::check()) {
            if ($request->isAjax()) {
                Response::error('Not authenticated.', [], 401);
            }
            Response::redirect(url('/login'));
        }
        return true;
    }
}
