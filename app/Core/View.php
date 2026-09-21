<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    private static string $basePath = __DIR__ . '/../Views/';
    private static ?string $layout = 'layouts/app';

    public static function exists(string $view): bool
    {
        return is_file(self::$basePath . $view . '.php');
    }

    public static function setLayout(?string $layout): void
    {
        self::$layout = $layout;
    }

    public static function render(string $view, array $data = []): void
    {
        $data['auth'] = Auth::user();
        $data['csrfToken'] = Csrf::token();
        extract($data, EXTR_SKIP);

        $file = self::$basePath . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $view");
        }

        ob_start();
        require $file;
        $content = ob_get_clean();

        if (self::$layout !== null && is_file(self::$basePath . self::$layout . '.php')) {
            $layoutFile = self::$basePath . self::$layout . '.php';
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    public static function partial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = self::$basePath . $view . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
}
