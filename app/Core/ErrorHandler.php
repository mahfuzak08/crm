<?php
declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

final class ErrorHandler
{
    private static bool $debug = false;

    public static function register(bool $debug): void
    {
        self::$debug = $debug;
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler([self::class, 'handle']);

        register_shutdown_function(static function (): void {
            $e = error_get_last();
            if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::handle(new ErrorException($e['message'], 0, $e['type'], $e['file'], $e['line']));
            }
        });
    }

    public static function handle(Throwable $e): void
    {
        $status = $e instanceof HttpException ? $e->status : 500;

        if ($status >= 500) {
            Logger::error($e->getMessage(), [
                'type'  => $e::class,
                'file'  => $e->getFile() . ':' . $e->getLine(),
                'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 15),
            ]);
        }

        $message = $status >= 500 ? 'Something went wrong. Please try again.' : $e->getMessage();

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, (self::$debug ? (string) $e : $message) . PHP_EOL);
            exit(1);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code($status);
        }

        if (Request::wantsJson()) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['ok' => false, 'message' => $message]);
            exit;
        }

        $debugInfo = self::$debug && $status >= 500 ? (string) $e : null;
        try {
            echo View::render('errors/error', compact('status', 'message', 'debugInfo'), null);
        } catch (Throwable) {
            echo '<h1>' . $status . '</h1><p>' . htmlspecialchars($message) . '</p>';
        }
        exit;
    }
}
