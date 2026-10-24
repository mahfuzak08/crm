<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Session;

/**
 * Active-company context. Every business query is scoped with Tenant::id().
 * Normal users are pinned to their own company; Super Admin may switch.
 */
final class Tenant
{
    private static ?array $company = null;

    public static function id(): int
    {
        $cid = (int) Session::get('cid', 0);
        if ($cid <= 0) {
            throw new HttpException(403, 'No active company.');
        }
        return $cid;
    }

    public static function company(): array
    {
        if (self::$company === null) {
            $cid = (int) Session::get('cid', 0);
            self::$company = $cid > 0
                ? (Database::fetch('SELECT * FROM companies WHERE id = ? AND deleted_at IS NULL', [$cid]) ?? [])
                : [];
        }
        return self::$company;
    }

    /** Companies the current user may operate in. */
    public static function allowedCompanies(): array
    {
        $user = AuthService::user();
        if (!$user) {
            return [];
        }
        if (AuthService::isSuperAdmin()) {
            return Database::fetchAll("SELECT id, name FROM companies WHERE deleted_at IS NULL AND status = 'active' ORDER BY name");
        }
        return Database::fetchAll('SELECT id, name FROM companies WHERE id = ?', [$user['company_id']]);
    }

    public static function switchTo(int $companyId): void
    {
        if (!AuthService::isSuperAdmin()) {
            throw new HttpException(403);
        }
        $ok = Database::value("SELECT id FROM companies WHERE id = ? AND deleted_at IS NULL AND status = 'active'", [$companyId]);
        if (!$ok) {
            throw new HttpException(404);
        }
        Session::put('cid', $companyId);
        self::$company = null;
        SettingsService::reset();
    }

    /**
     * Load a record by id, guaranteeing it belongs to the active company (IDOR protection).
     * $table must be a code constant.
     */
    public static function findOrFail(string $table, int $id, bool $withTrashed = false): array
    {
        $sql = 'SELECT * FROM ' . Database::ident($table) . ' WHERE id = ? AND company_id = ?';
        if (!$withTrashed) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $row = Database::fetch($sql, [$id, self::id()]);
        if (!$row) {
            throw new HttpException(404);
        }
        return $row;
    }

    /** True if $id exists in $table for the active company (used by validators). */
    public static function owns(string $table, mixed $id, string $extraWhere = '', array $extraParams = []): bool
    {
        if (!is_numeric($id) || (int) $id <= 0) {
            return false;
        }
        $sql = 'SELECT 1 FROM ' . Database::ident($table) . ' WHERE id = ? AND company_id = ?' . ($extraWhere ? ' AND ' . $extraWhere : '');
        return (bool) Database::value($sql, [(int) $id, self::id(), ...$extraParams]);
    }
}
