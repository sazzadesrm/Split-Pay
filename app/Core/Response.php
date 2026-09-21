<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public static function success(mixed $data = null, string $message = '', int $status = 200): void
    {
        self::json(['success' => true, 'message' => $message, 'data' => $data, 'errors' => []], $status);
    }

    public static function error(string $message, array $errors = [], int $status = 422): void
    {
        self::json(['success' => false, 'message' => $message, 'data' => null, 'errors' => $errors], $status);
    }

    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    public static function view(string $view, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        View::render($view, $data);
    }

    public static function abort(int $status, string $message = ''): void
    {
        http_response_code($status);
        $map = [403 => 'errors/403', 404 => 'errors/404', 419 => 'errors/419', 429 => 'errors/429', 500 => 'errors/500'];
        if (isset($map[$status]) && View::exists($map[$status])) {
            View::render($map[$status], ['message' => $message]);
        } else {
            echo htmlspecialchars($message !== '' ? $message : 'Error ' . $status, ENT_QUOTES);
        }
        exit;
    }
}
