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
        // Own save path: shared hosts' cron purges the default one after ~24 min,
        // whatever gc_maxlifetime we ask for.
        $dir = BASE_PATH . '/storage/sessions';
        if (is_dir($dir) || @mkdir($dir, 0700, true)) {
            session_save_path($dir);
            ini_set('session.gc_probability', '1');
            ini_set('session.gc_divisor', '100');
        }
        $app = self::isApp();
        $idle = $app ? (int) config('session.app_idle_timeout', 2592000) : (int) config('session.idle_timeout', 7200);
        $abs  = $app ? (int) config('session.app_absolute_timeout', 31536000) : (int) config('session.absolute_timeout', 43200);
        // GC is shared by web and app sessions, so keep files as long as the longest window.
        ini_set('session.gc_maxlifetime', (string) max(1800, $idle, (int) config('session.app_idle_timeout', 2592000)));

        session_name((string) config('session.name', 'lakecrm'));
        session_set_cookie_params([
            // App: persistent cookie so WebView keeps it across restarts. Browser: until closed.
            'lifetime' => $app ? $idle : 0,
            'path'     => base_path_prefix() . '/',
            'secure'   => Request::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        // Idle + absolute timeout for authenticated sessions.
        $now = time();
        if (isset($_SESSION['uid'])) {
            if (($now - (int) ($_SESSION['last_activity'] ?? $now)) > $idle || ($now - (int) ($_SESSION['created'] ?? $now)) > $abs) {
                self::destroy();
                session_start();
                self::flash('warning', 'Your session expired. Please sign in again.');
            }
        }
        $_SESSION['last_activity'] = $now;
        $_SESSION['created'] ??= $now;

        // PHP never extends a cookie's expiry by itself; slide it forward on each app request.
        if ($app && isset($_COOKIE[session_name()])) {
            $p = session_get_cookie_params();
            setcookie(session_name(), session_id(), [
                'expires' => $now + $idle, 'path' => $p['path'], 'secure' => $p['secure'],
                'httponly' => true, 'samesite' => $p['samesite'] ?: 'Lax',
            ]);
        }

        // Rotate flash: data set during previous request is available now, then removed.
        $_SESSION['_flash_now'] = $_SESSION['_flash_next'] ?? [];
        unset($_SESSION['_flash_next']);
    }

    /** Request comes from the Lakeview Android app (its WebView appends "LakeviewApp/<version>"). */
    public static function isApp(): bool
    {
        return str_contains((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 'LakeviewApp/');
    }

    /** User-Agent hash without version numbers, so browser/WebView/app updates don't sign users out. */
    public static function uaFingerprint(): string
    {
        return hash('sha256', (string) preg_replace('/\d+(?:[._]\d+)*/', '', Request::userAgent()));
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
