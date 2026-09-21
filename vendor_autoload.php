<?php
declare(strict_types=1);

/**
 * Minimal PSR-4 autoloader for the App\ namespace so the application runs
 * without requiring `composer install` (Composer is still used for
 * PHPUnit in development; see composer.json).
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

require __DIR__ . '/app/Core/helpers.php';
