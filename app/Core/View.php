<?php
declare(strict_types=1);

namespace App\Core;

/** Plain PHP templates. Views live in /views; layouts in /views/layouts. */
final class View
{
    private static array $sections = [];
    private static array $stack = [];

    /** Capture a named block inside a view, e.g. page header action buttons. */
    public static function start(string $name): void
    {
        self::$stack[] = $name;
        ob_start();
    }

    public static function end(): void
    {
        $name = array_pop(self::$stack);
        self::$sections[$name] = (string) ob_get_clean();
    }

    public static function section(string $name): string
    {
        return self::$sections[$name] ?? '';
    }

    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): string
    {
        $content = self::capture($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::capture($layout, $data + ['content' => $content]);
    }

    public static function partial(string $view, array $data = []): string
    {
        return self::capture($view, $data);
    }

    private static function capture(string $view, array $data): string
    {
        if (!preg_match('#^[a-z0-9_/\-]+$#i', $view)) {
            throw new \InvalidArgumentException('Invalid view name');
        }
        $file = VIEW_PATH . '/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('View not found: ' . $view);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
