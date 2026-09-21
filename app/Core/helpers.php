<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(string $file): array
    {
        static $cache = [];
        if (!isset($cache[$file])) {
            $cache[$file] = require dirname(__DIR__, 2) . "/config/$file.php";
        }
        return $cache[$file];
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = config('app')['url'];
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(Csrf::token()) . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        return e($_SESSION['_old'][$key] ?? $default);
    }
}

if (!function_exists('flash_get')) {
    function flash_get(string $key): mixed
    {
        return \App\Core\Session::flash($key);
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('money_fmt')) {
    function money_fmt(string|int $amount, string $currency = 'USD'): string
    {
        $cents = is_int($amount) ? $amount : \App\Core\Money::toCents((string) $amount);
        return \App\Core\Money::format($cents, $currency);
    }
}

if (!function_exists('csv_safe')) {
    function csv_safe(?string $value): string
    {
        $value = (string) $value;
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }
        return $value;
    }
}

if (!function_exists('status_badge_class')) {
    function status_badge_class(string $status): string
    {
        return match ($status) {
            'draft' => 'bg-secondary',
            'submitted' => 'bg-info text-dark',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'reimbursed' => 'bg-primary',
            'pending' => 'bg-warning text-dark',
            'completed' => 'bg-success',
            'cancelled' => 'bg-secondary',
            'active' => 'bg-success',
            'paused' => 'bg-warning text-dark',
            'archived' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $datetime, string $format = 'M j, Y'): string
    {
        if (!$datetime) {
            return '';
        }
        return date($format, strtotime($datetime));
    }
}

if (!function_exists('role_badge_class')) {
    function role_badge_class(string $role): string
    {
        return match ($role) {
            'owner' => 'bg-indigo',
            'admin' => 'bg-primary',
            'member' => 'bg-info text-dark',
            'viewer' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }
}

if (!function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $letters = array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));
        return implode('', $letters) ?: '?';
    }
}
