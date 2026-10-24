<?php
declare(strict_types=1);

namespace App\Core;

/** Per-session synchronizer token, sent as form field `_token` or header X-CSRF-Token. */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . self::token() . '">';
    }

    public static function verify(): void
    {
        $sent = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($sent) || empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $sent)) {
            Logger::security('CSRF token mismatch', ['path' => Request::path()]);
            throw new HttpException(403, HttpException::defaultMessage(419));
        }
    }
}
