<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    /** Request path relative to the application base, always starting with "/". */
    public static function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri = rawurldecode($uri);
        $base = base_path_prefix();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        if (str_starts_with($uri, '/public/')) { // direct access to /public/ when root rewrite not used
            $uri = substr($uri, 7);
        }
        $uri = '/' . trim($uri, '/');
        return $uri === '/index.php' ? '/' : $uri;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    /** Filter values from the query string as [key => string]; non-strings dropped. */
    public static function filters(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            $v = $_GET[$k] ?? '';
            $out[$k] = is_string($v) ? trim($v) : '';
        }
        return $out;
    }

    /** String-only GET param (arrays rejected). */
    public static function str(string $key, string $default = ''): string
    {
        $v = $_GET[$key] ?? $_POST[$key] ?? $default;
        return is_string($v) ? trim($v) : $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = $_GET[$key] ?? $_POST[$key] ?? null;
        return is_string($v) && preg_match('/^\d{1,10}$/', trim($v)) ? (int) $v : $default;
    }

    /** POST body (trimmed strings, arrays kept). */
    public static function post(): array
    {
        return self::trimAll($_POST);
    }

    /** Only the listed POST keys — protection against mass assignment. */
    public static function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = isset($_POST[$k]) ? self::trimAll($_POST[$k]) : null;
        }
        return $out;
    }

    public static function file(string $key): ?array
    {
        $f = $_FILES[$key] ?? null;
        if (!is_array($f) || !isset($f['error']) || is_array($f['error']) || $f['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $f;
    }

    public static function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    public static function wantsJson(): bool
    {
        return self::isAjax() && str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    public static function ip(): string
    {
        // REMOTE_ADDR only; trusting X-Forwarded-For would allow spoofing rate limits.
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    }

    public static function userAgent(): string
    {
        return mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    private static function trimAll(mixed $v): mixed
    {
        if (is_array($v)) {
            return array_map([self::class, 'trimAll'], $v);
        }
        return is_string($v) ? trim($v) : $v;
    }
}
