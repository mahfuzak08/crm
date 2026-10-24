<?php
declare(strict_types=1);

namespace App\Core;

/** Hardened session handling + flash data. */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');   // reject uninitialised IDs (anti-fixation)
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        if (PHP_VERSION_ID < 80400) {               // deprecated in 8.4 (its defaults are secure)
            ini_set('session.sid_length', '48');
            ini_set('session.sid_bits_per_character', '6');
        }
        ini_set('session.gc_maxlifetime', (string) max(1800, (int) config('session.idle_timeout')));

        session_name((string) config('session.name', 'lakecrm'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => base_path_prefix() . '/',
            'secure'   => Request::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        // Idle + absolute timeout for authenticated sessions.
        $now = time();
        if (isset($_SESSION['uid'])) {
            $idle = (int) config('session.idle_timeout', 1800);
            $abs  = (int) config('session.absolute_timeout', 43200);
            if (($now - (int) ($_SESSION['last_activity'] ?? $now)) > $idle || ($now - (int) ($_SESSION['created'] ?? $now)) > $abs) {
                self::destroy();
                session_start();
                self::flash('warning', 'Your session expired. Please sign in again.');
            }
        }
        $_SESSION['last_activity'] = $now;
        $_SESSION['created'] ??= $now;

        // Rotate flash: data set during previous request is available now, then removed.
        $_SESSION['_flash_now'] = $_SESSION['_flash_next'] ?? [];
        unset($_SESSION['_flash_next']);
    }

    /** New session ID, keeping data. Call on login / privilege change. */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000, 'path' => $p['path'], 'secure' => $p['secure'],
                'httponly' => true, 'samesite' => $p['samesite'] ?: 'Lax',
            ]);
            session_destroy();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Flash for the next request. */
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash_next'][$key] = $value;
    }

    /** Read flash set by the previous request. */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_flash_now'][$key] ?? $default;
    }
}
