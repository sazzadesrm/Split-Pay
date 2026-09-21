<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;

final class CsrfMiddleware
{
    public function handle(Request $request): bool
    {
        if ($request->method !== 'POST') {
            return true;
        }
        $token = $request->input('csrf_token') ?? $request->header('X-CSRF-Token');
        if (!Csrf::verify($token)) {
            \App\Core\Logger::security('CSRF verification failed for ' . $request->path);
            if ($request->isAjax()) {
                Response::error('Your session has expired. Please refresh and try again.', [], 419);
            }
            Response::abort(419, 'Your session has expired. Please refresh and try again.');
        }
        return true;
    }
}
