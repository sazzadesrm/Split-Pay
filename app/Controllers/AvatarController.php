<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;

final class AvatarController extends Controller
{
    public function show(Request $request, string $userId): void
    {
        $user = (new User())->find((int) $userId);
        if ($user === null || empty($user['avatar_path'])) {
            Response::abort(404);
        }
        $config = config('app');
        $storageRoot = dirname(rtrim($config['avatar_path'], '/'));
        $full = realpath($storageRoot . '/' . $user['avatar_path']);
        $storageRealpath = realpath($storageRoot);
        if ($full === false || $storageRealpath === false || !str_starts_with($full, $storageRealpath) || !is_file($full)) {
            Response::abort(404);
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . $finfo->file($full));
        header('Cache-Control: private, max-age=3600');
        readfile($full);
        exit;
    }
}
