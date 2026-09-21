<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use InvalidArgumentException;

final class ProfileController extends Controller
{
    public function show(Request $request): void
    {
        Response::view('profile/index', ['title' => 'Profile', 'user' => Auth::user()]);
    }

    public function update(Request $request): void
    {
        $data = $request->all();
        $service = new AuthService();
        $service->updateProfile($this->userId(), $data['name'] ?? '', $data['email'] ?? '', $data['default_currency'] ?? 'USD');
        $this->respond($request, true, 'Profile updated.', null, '/profile');
    }

    public function updatePassword(Request $request): void
    {
        $current = (string) $request->input('current_password', '');
        $new = (string) $request->input('new_password', '');
        $confirm = (string) $request->input('new_password_confirmation', '');

        if (strlen($new) < 8 || $new !== $confirm) {
            $this->respond($request, false, 'New passwords must match and be at least 8 characters.', null, '/profile');
            return;
        }

        $ok = (new AuthService())->changePassword($this->userId(), $current, $new);
        $this->respond($request, $ok, $ok ? 'Password updated.' : 'Current password is incorrect.', null, '/profile');
    }

    public function updateAvatar(Request $request): void
    {
        $file = $request->file('avatar');
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->respond($request, false, 'Please choose an image to upload.', null, '/profile');
            return;
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            $this->respond($request, false, 'Avatar must be 2 MB or smaller.', null, '/profile');
            return;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) {
            $this->respond($request, false, 'Only JPEG, PNG or WEBP images are allowed.', null, '/profile');
            return;
        }

        $config = config('app');
        $dir = rtrim($config['avatar_path'], '/');
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $filename = bin2hex(random_bytes(24)) . '.' . $allowed[$mime];
        move_uploaded_file($file['tmp_name'], $dir . '/' . $filename);

        (new \App\Models\User())->update($this->userId(), ['avatar_path' => 'avatars/' . $filename]);
        $this->respond($request, true, 'Avatar updated.', null, '/profile');
    }
}
