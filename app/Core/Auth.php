<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    private static ?array $userCache = null;

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        self::$userCache = $user;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$userCache = null;
    }

    public static function check(): bool
    {
        return Session::has('user_id');
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        if (self::$userCache === null) {
            self::$userCache = (new User())->find((int) self::id());
        }
        return self::$userCache;
    }

    public static function activeTeamId(): ?int
    {
        return Session::get('active_team_id');
    }

    public static function setActiveTeamId(int $teamId): void
    {
        Session::set('active_team_id', $teamId);
    }
}
