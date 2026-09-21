<?php
declare(strict_types=1);

use App\Core\Env;
use App\Core\Session;

require __DIR__ . '/../vendor_autoload.php';

Env::load(dirname(__DIR__) . '/.env');

$appConfig = require dirname(__DIR__) . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

if ($appConfig['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

set_exception_handler(function (Throwable $e) use ($appConfig): void {
    \App\Core\Logger::error($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if ($appConfig['debug']) {
        echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES) . '</pre>';
    } else {
        echo 'An unexpected error occurred. Please try again later.';
    }
});

Session::start();
