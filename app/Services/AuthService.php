<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Session;
use App\Core\ValidationException;

final class AuthService
{
    private static ?array $user = null;
    private static bool $loaded = false;
    private static array $permissions = [];
    private static bool $super = false;

    // Used to equalise timing when the account does not exist.
    private const DUMMY_HASH = '$2y$10$BKNIWTQp8QtWd.xWbRJgL.Slj.DTTLkI1WzrxQsEHYcT2kq3i09Yy';

    /* ---------------- login / logout ---------------- */

    public static function attempt(string $identifier, string $password): array
    {
        $identifier = mb_substr(trim($identifier), 0, 150);
        $ip = Request::ip();
        self::throttle($identifier, $ip);

        $user = self::findForLogin($identifier);
        $valid = password_verify($password, $user['password'] ?? self::DUMMY_HASH);

        if (!$user || !$valid) {
            self::recordAttempt($user['id'] ?? null, $identifier, false);
            Logger::security('Failed login', ['identifier' => $identifier]);
            throw ValidationException::rule('Invalid credentials.');
        }
        if ($user['status'] !== 'active' || $user['company_status'] !== 'active') {
            self::recordAttempt((int) $user['id'], $identifier, false);
            throw ValidationException::rule('Your account is inactive. Please contact the administrator.');
        }

        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            Database::update('users', ['password' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$user['id']]);
        }

        self::recordAttempt((int) $user['id'], $identifier, true);
        self::loginUser($user);
        return $user;
    }

    private static function findForLogin(string $identifier): ?array
    {
        $fields = array_intersect(
            array_map('trim', explode(',', (string) \App\Core\Env::get('LOGIN_FIELDS', 'email,username,phone'))),
            ['email', 'username', 'phone']
        );
        if ($fields === [] || $identifier === '') {
            return null;
        }
        $conds = array_map(static fn($f) => "u.$f = ?", $fields);
        return Database::fetch(
            'SELECT u.*, c.status AS company_status FROM users u JOIN companies c ON c.id = u.company_id
             WHERE u.deleted_at IS NULL AND c.deleted_at IS NULL AND (' . implode(' OR ', $conds) . ') LIMIT 1',
            array_fill(0, count($fields), $identifier)
        );
    }

    private static function loginUser(array $user): void
    {
        Session::regenerate(); // prevent session fixation
        $_SESSION['uid'] = (int) $user['id'];
        $_SESSION['cid'] = (int) $user['company_id'];
        $_SESSION['sv'] = (int) $user['session_version'];
        $_SESSION['ua'] = hash('sha256', Request::userAgent());
        $_SESSION['last_activity'] = time();
        unset($_SESSION['_csrf']);

        Database::update('users', ['last_login_at' => now(), 'last_login_ip' => Request::ip()], 'id = ?', [$user['id']]);
        self::$loaded = false;
        AuditLogService::log('LOGIN', 'auth', (int) $user['id']);
    }

    public static function logout(): void
    {
        if (self::id()) {
            AuditLogService::log('LOGOUT', 'auth', self::id());
        }
        Session::destroy();
        self::$user = null;
        self::$loaded = true;
    }

    /** Brute-force protection: per identifier+IP and per IP. */
    private static function throttle(string $identifier, string $ip): void
    {
        $mins = (int) config('auth.lockout_minutes', 15);
        $since = date('Y-m-d H:i:s', time() - $mins * 60);
        $byIdent = (int) Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND ip = ? AND success = 0 AND created_at >= ?',
            [$identifier, $ip, $since]
        );
        $byIp = (int) Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND success = 0 AND created_at >= ?',
            [$ip, $since]
        );
        if ($byIdent >= (int) config('auth.max_attempts', 5) || $byIp >= (int) config('auth.max_ip_attempts', 25)) {
            Logger::security('Login throttled', ['identifier' => $identifier]);
            throw ValidationException::rule("Too many failed attempts. Please try again in $mins minutes.");
        }
    }

    private static function recordAttempt(?int $userId, string $identifier, bool $success): void
    {
        Database::insert('login_attempts', [
            'user_id' => $userId, 'identifier' => $identifier, 'ip' => Request::ip(),
            'user_agent' => Request::userAgent(), 'success' => $success ? 1 : 0,
        ]);
        if ($success) {
            // clear previous failures for this identifier/IP
            Database::run('DELETE FROM login_attempts WHERE identifier = ? AND ip = ? AND success = 0', [$identifier, Request::ip()]);
        }
    }

    /* ---------------- current user ---------------- */

    public static function id(): ?int
    {
        return isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : null;
    }

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;
        $uid = self::id();
        if (!$uid) {
            return null;
        }
        $user = Database::fetch(
            "SELECT u.id, u.company_id, u.name, u.email, u.phone, u.username, u.status, u.avatar, u.session_version, u.last_login_at
             FROM users u JOIN companies c ON c.id = u.company_id
             WHERE u.id = ? AND u.deleted_at IS NULL AND u.status = 'active' AND c.status = 'active' AND c.deleted_at IS NULL",
            [$uid]
        );
        $uaOk = hash_equals((string) ($_SESSION['ua'] ?? ''), hash('sha256', Request::userAgent()));
        if (!$user || (int) $user['session_version'] !== (int) ($_SESSION['sv'] ?? 0) || !$uaOk) {
            // deactivated, password changed elsewhere, or hijack attempt
            if (!$uaOk) {
                Logger::security('Session user-agent mismatch', ['uid' => $uid]);
            }
            Session::destroy();
            Session::start();
            return self::$user = null;
        }

        $roles = Database::fetchAll(
            'SELECT r.id, r.slug, r.name FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = ?',
            [$uid]
        );
        $user['roles'] = $roles;
        self::$super = in_array('super_admin', array_column($roles, 'slug'), true);
        self::$permissions = self::$super ? [] : array_flip(Database::pairs(
            'SELECT DISTINCT p.id, p.name FROM user_roles ur
             JOIN role_permissions rp ON rp.role_id = ur.role_id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE ur.user_id = ?',
            [$uid]
        ));

        // Non-super users are always pinned to their own company.
        if (!self::$super) {
            $_SESSION['cid'] = (int) $user['company_id'];
        }
        return self::$user = $user;
    }

    public static function isSuperAdmin(): bool
    {
        self::user();
        return self::$super;
    }

    public static function can(string $permission): bool
    {
        if (!self::user()) {
            return false;
        }
        return self::$super || isset(self::$permissions[$permission]);
    }

    /** Permission names held by the current user (Super Admin: all). */
    public static function permissionNames(): array
    {
        self::user();
        return self::$super ? array_column(Database::fetchAll('SELECT name FROM permissions'), 'name') : array_keys(self::$permissions);
    }

    /**
     * Anti-escalation: a non-Super-Admin may only grant/manage permission sets
     * that are a subset of their own.
     */
    public static function assertCanGrant(array $permissionNames): void
    {
        if (self::isSuperAdmin()) {
            return;
        }
        $missing = array_diff($permissionNames, self::permissionNames());
        if ($missing) {
            Logger::security('Privilege escalation blocked', ['missing' => array_values($missing)]);
            throw new HttpException(403, 'You cannot grant or manage permissions you do not hold yourself (' . implode(', ', array_slice($missing, 0, 5)) . ').');
        }
    }

    public static function authorize(string $permission): void
    {
        if (!self::can($permission)) {
            Logger::security('Permission denied', ['permission' => $permission, 'path' => Request::path()]);
            throw new HttpException(403);
        }
    }

    /* ---------------- passwords ---------------- */

    public static function changePassword(int $userId, string $newPassword, bool $bumpSession = true): void
    {
        Database::run(
            'UPDATE users SET password = ?, password_changed_at = NOW()' . ($bumpSession ? ', session_version = session_version + 1' : '') . ' WHERE id = ?',
            [password_hash($newPassword, PASSWORD_DEFAULT), $userId]
        );
        if ($bumpSession && $userId === self::id()) {
            // keep the current session valid
            $_SESSION['sv'] = (int) Database::value('SELECT session_version FROM users WHERE id = ?', [$userId]);
            Session::regenerate();
        }
    }

    /** Create a reset token; returns the plain token (sent by email) or null if no such user. */
    public static function createResetToken(string $email): ?array
    {
        $user = Database::fetch("SELECT id, name, email, company_id FROM users WHERE email = ? AND deleted_at IS NULL AND status = 'active'", [strtolower(trim($email))]);
        if (!$user) {
            return null;
        }
        $token = bin2hex(random_bytes(32));
        Database::run('DELETE FROM password_resets WHERE user_id = ?', [$user['id']]);
        Database::insert('password_resets', [
            'user_id' => $user['id'],
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + 60 * (int) config('auth.reset_token_minutes', 60)),
        ]);
        return ['user' => $user, 'token' => $token];
    }

    public static function findResetToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return Database::fetch(
            'SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()',
            [hash('sha256', $token)]
        );
    }

    public static function resetWithToken(string $token, string $password): void
    {
        Database::transaction(function () use ($token, $password) {
            $row = self::findResetToken($token);
            if (!$row) {
                throw ValidationException::rule('This reset link is invalid or has expired.');
            }
            Database::update('password_resets', ['used_at' => now()], 'id = ?', [$row['id']]);
            self::changePassword((int) $row['user_id'], $password); // invalidates all sessions
            AuditLogService::log('PASSWORD_RESET', 'auth', (int) $row['user_id'], null, null, null);
        });
    }
}
