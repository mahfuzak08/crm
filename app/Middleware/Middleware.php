<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

/**
 * Route middleware resolver:
 *   'auth'        -> must be logged in
 *   'guest'       -> must NOT be logged in
 *   'can:x.y'     -> must hold permission x.y
 *   'super'       -> must be Super Admin
 */
final class Middleware
{
    public static function handle(string $name): void
    {
        [$key, $arg] = array_pad(explode(':', $name, 2), 2, null);
        match ($key) {
            'auth'  => self::auth(),
            'guest' => self::guest(),
            'can'   => self::auth() ?? AuthService::authorize((string) $arg),
            'super' => self::auth() ?? (AuthService::isSuperAdmin() ? null : throw new HttpException(403)),
            default => throw new \InvalidArgumentException('Unknown middleware ' . $key),
        };
    }

    private static function auth(): null
    {
        if (AuthService::user() === null) {
            if (Request::isAjax()) {
                throw new HttpException(401, 'Your session has expired. Please sign in again.');
            }
            if (Request::method() === 'GET') {
                Session::put('intended', Request::path() . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']));
            }
            Response::redirect('/login');
        }
        self::applyCompanyTimezone();
        return null;
    }

    /** Use the active company's timezone for PHP and the DB session. */
    private static function applyCompanyTimezone(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $tz = (string) (\App\Services\Tenant::company()['timezone'] ?? '');
        if ($tz !== '' && $tz !== date_default_timezone_get() && in_array($tz, \DateTimeZone::listIdentifiers(), true)) {
            date_default_timezone_set($tz);
            \App\Core\Database::run('SET time_zone = ?', [(new \DateTime())->format('P')]);
        }
    }

    private static function guest(): void
    {
        if (AuthService::user() !== null) {
            Response::redirect('/');
        }
    }
}
