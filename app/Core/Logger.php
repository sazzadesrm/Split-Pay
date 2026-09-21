<?php
declare(strict_types=1);

namespace App\Core;

final class Logger
{
    private static function path(string $channel): string
    {
        $base = (require dirname(__DIR__, 2) . '/config/app.php')['storage_path'] . '/logs';
        if (!is_dir($base)) {
            @mkdir($base, 0755, true);
        }
        return $base . '/' . $channel . '.log';
    }

    private static function write(string $channel, string $level, string $message): void
    {
        $line = sprintf('[%s] %s: %s%s', date('Y-m-d H:i:s'), strtoupper($level), $message, PHP_EOL);
        @file_put_contents(self::path($channel), $line, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $message): void
    {
        self::write('error', 'error', $message);
    }

    public static function security(string $message): void
    {
        self::write('security', 'security', $message);
    }

    public static function app(string $message): void
    {
        self::write('app', 'info', $message);
    }

    public static function mail(string $message): void
    {
        self::write('mail', 'info', $message);
    }

    public static function scheduler(string $message): void
    {
        self::write('scheduler', 'info', $message);
    }
}
