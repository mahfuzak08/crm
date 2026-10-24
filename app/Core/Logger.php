<?php
declare(strict_types=1);

namespace App\Core;

/** JSON-lines logger with channels: app, error, security. */
final class Logger
{
    public static function app(string $message, array $context = []): void
    {
        self::write('app', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function security(string $message, array $context = []): void
    {
        self::write('security', $message, $context);
    }

    private static function write(string $channel, string $message, array $context): void
    {
        $dir = STORAGE_PATH . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $line = json_encode([
            'time'    => date('c'),
            'message' => $message,
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'cli',
            'uri'     => $_SERVER['REQUEST_URI'] ?? null,
            'user_id' => $_SESSION['uid'] ?? null,
            'context' => $context,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        @file_put_contents($dir . '/' . $channel . '-' . date('Y-m-d') . '.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
