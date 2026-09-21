<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\LoginAttempt;
use App\Models\PasswordReset;
use App\Models\User;

final class AuthService
{
    private User $users;
    private LoginAttempt $attempts;
    private PasswordReset $resets;

    public function __construct()
    {
        $this->users = new User();
        $this->attempts = new LoginAttempt();
        $this->resets = new PasswordReset();
    }

    public function register(string $name, string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));
        $id = $this->users->insert([
            'name' => trim($name),
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'default_currency' => 'USD',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->users->find($id);
    }

    public function isLockedOut(string $email, int $maxAttempts, int $windowMinutes): bool
    {
        return $this->attempts->recentFailedCount(mb_strtolower(trim($email)), $windowMinutes) >= $maxAttempts;
    }

    public function attempt(string $email, string $password, string $ip): ?array
    {
        $email = mb_strtolower(trim($email));
        $user = $this->users->findByEmail($email);
        $ok = $user !== null && password_verify($password, $user['password_hash']);
        $this->attempts->record($email, $ip, $ok);
        return $ok ? $user : null;
    }

    public function createPasswordReset(string $email, int $expiryMinutes): ?array
    {
        $user = $this->users->findByEmail(mb_strtolower(trim($email)));
        if ($user === null) {
            return null;
        }
        $this->resets->invalidateForUser((int) $user['id']);
        $token = bin2hex(random_bytes(32));
        $this->resets->insert([
            'user_id' => $user['id'],
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + $expiryMinutes * 60),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return ['user' => $user, 'token' => $token];
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $reset = $this->resets->findValidByHash(hash('sha256', $token));
        if ($reset === null) {
            return false;
        }
        $this->users->update((int) $reset['user_id'], [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);
        $this->resets->markUsed((int) $reset['id']);
        $this->resets->invalidateForUser((int) $reset['user_id']);
        return true;
    }

    public function updateProfile(int $userId, string $name, string $email, string $currency): array
    {
        $this->users->update($userId, [
            'name' => trim($name),
            'email' => mb_strtolower(trim($email)),
            'default_currency' => $currency,
        ]);
        return $this->users->find($userId);
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->users->find($userId);
        if ($user === null || !password_verify($currentPassword, $user['password_hash'])) {
            return false;
        }
        $this->users->update($userId, ['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)]);
        return true;
    }
}
