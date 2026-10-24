<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Query;
use App\Core\ValidationException;
use App\Core\Validator;

final class UserService
{
    public static function list(array $f, int $page): array
    {
        $q = Query::table('users u')
            ->select("u.id, u.name, u.email, u.phone, u.username, u.status, u.last_login_at, u.created_at,
                      (SELECT GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ', ') FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = u.id) AS roles")
            ->where('u.company_id = ?', Tenant::id())
            ->where('u.deleted_at IS NULL')
            ->search(['u.name', 'u.email', 'u.phone', 'u.username'], $f['q'] ?? '')
            ->whereIf($f['status'] ?? '', 'u.status = ?')
            ->whereIf($f['role'] ?? '', 'EXISTS (SELECT 1 FROM user_roles ur2 WHERE ur2.user_id = u.id AND ur2.role_id = ?)', (int) ($f['role'] ?? 0))
            ->sort($f['sort'] ?? null, $f['dir'] ?? null, ['name' => 'u.name', 'last_login' => 'u.last_login_at', 'created' => 'u.created_at'], 'u.name ASC');
        return $q->paginate($page);
    }

    /** Non-super actors may only manage users whose permissions are a subset of their own. */
    public static function assertManageable(array $user): void
    {
        if ($user['is_super'] && !AuthService::isSuperAdmin()) {
            throw new HttpException(403, 'Only a Super Admin can manage a Super Admin account.');
        }
        if ((int) $user['id'] !== AuthService::id()) {
            AuthService::assertCanGrant(RoleService::permissionNamesForRoles($user['role_ids']));
        }
    }

    public static function find(int $id): array
    {
        $user = Tenant::findOrFail('users', $id);
        $user['role_ids'] = array_map('intval', array_column(Database::fetchAll('SELECT role_id FROM user_roles WHERE user_id = ?', [$id]), 'role_id'));
        $user['is_super'] = (bool) Database::value("SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = ? AND r.slug = 'super_admin'", [$id]);
        return $user;
    }

    /** Users of the active company for "assign to" dropdowns: [id => name]. */
    public static function options(): array
    {
        static $c = null;
        return $c ??= Database::pairs("SELECT id, name FROM users WHERE company_id = ? AND deleted_at IS NULL AND status = 'active' ORDER BY name", [Tenant::id()]);
    }

    public static function save(array $input, ?int $id = null): int
    {
        $existing = $id ? self::find($id) : null;
        if ($existing) {
            self::assertManageable($existing);
        }
        $uniq = static fn(string $col) => static fn($v) => Database::value("SELECT 1 FROM users WHERE $col = ? AND id <> ?", [$v, $id ?? 0]) ? 'Already in use.' : null;
        $rules = [
            'name'     => 'required|max:150',
            'email'    => ['nullable', 'email', $uniq('email')],
            'phone'    => ['nullable', 'phone', $uniq('phone')],
            'username' => ['required', 'username', $uniq('username')],
            'status'   => 'required|in:active,inactive',
        ];
        if (!$id) {
            $rules['password'] = 'required|password';
            $rules['password_confirmation'] = 'required|same:password';
        }
        $data = Validator::make($input, $rules, ['password_confirmation' => 'Password']);
        unset($data['password_confirmation']);

        // Company assignment: only Super Admin may place a user in another company.
        $companyId = Tenant::id();
        if (AuthService::isSuperAdmin() && !empty($input['company_id'])) {
            $companyId = (int) $input['company_id'];
            if (!Database::value('SELECT 1 FROM companies WHERE id = ? AND deleted_at IS NULL', [$companyId])) {
                throw new ValidationException(['company_id' => 'Invalid company.']);
            }
        }

        $roleIds = self::validRoleIds((array) ($input['roles'] ?? []), $companyId);
        if (!$roleIds) {
            throw new ValidationException(['roles' => 'Assign at least one role.']);
        }
        AuthService::assertCanGrant(RoleService::permissionNamesForRoles($roleIds));
        if ($id === AuthService::id() && $data['status'] !== 'active') {
            throw ValidationException::rule('You cannot deactivate your own account.');
        }
        if ($existing && $existing['is_super'] && $id === AuthService::id()) {
            $superId = (int) Database::value("SELECT id FROM roles WHERE slug = 'super_admin' AND company_id IS NULL");
            if (!in_array($superId, $roleIds, true)) {
                throw ValidationException::rule('You cannot remove the Super Admin role from your own account.');
            }
        }

        return Database::transaction(static function () use ($data, $id, $companyId, $roleIds, $existing) {
            $data['company_id'] = $companyId;
            if ($id) {
                Database::update('users', $data, 'id = ?', [$id]);
                if ($data['status'] !== 'active') {
                    Database::run('UPDATE users SET session_version = session_version + 1 WHERE id = ?', [$id]);
                }
            } else {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                $data['created_by'] = AuthService::id();
                $data['password_changed_at'] = now();
                $id = Database::insert('users', $data);
            }
            $oldRoles = $existing['role_ids'] ?? [];
            // Preserve super_admin role (cannot be granted/removed by non-super users)
            Database::run('DELETE FROM user_roles WHERE user_id = ?', [$id]);
            foreach ($roleIds as $rid) {
                Database::insert('user_roles', ['user_id' => $id, 'role_id' => $rid]);
            }
            if ($oldRoles != $roleIds) {
                Database::run('UPDATE users SET session_version = session_version + 1 WHERE id = ? AND id <> ?', [$id, (int) AuthService::id()]);
            }
            // Users with the Customer role (e.g. directors who also buy shares) get a linked customer record.
            if (self::hasCustomerRole($roleIds)) {
                CustomerService::syncFromUser($id, $companyId);
            }
            $logNew = $data + ['roles' => implode(',', $roleIds)];
            unset($logNew['password']);
            AuditLogService::log($existing ? 'UPDATE_USER' : 'CREATE_USER', 'users', $id,
                $existing ? array_intersect_key($existing, $data) + ['roles' => implode(',', $oldRoles)] : null, $logNew);
            return $id;
        });
    }

    /** Keep only roles visible to the company; super_admin only grantable by a Super Admin. */
    private static function validRoleIds(array $ids, int $companyId): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }
        $rows = Database::fetchAll(
            'SELECT id, slug FROM roles WHERE id IN (' . Database::placeholders($ids) . ') AND (company_id IS NULL OR company_id = ?)',
            [...$ids, $companyId]
        );
        $out = [];
        foreach ($rows as $r) {
            if ($r['slug'] === 'super_admin' && !AuthService::isSuperAdmin()) {
                continue;
            }
            $out[] = (int) $r['id'];
        }
        sort($out);
        return $out;
    }

    public static function hasCustomerRole(array $roleIds): bool
    {
        return $roleIds && (bool) Database::value(
            "SELECT 1 FROM roles WHERE slug = 'customer' AND id IN (" . Database::placeholders($roleIds) . ')', $roleIds
        );
    }

    public static function resetPassword(int $id, array $input): void
    {
        $user = self::find($id);
        self::assertManageable($user);
        Validator::make($input, ['password' => 'required|password', 'password_confirmation' => 'required|same:password'], ['password_confirmation' => 'Password']);
        AuthService::changePassword($id, (string) $input['password']);
        AuditLogService::log('RESET_USER_PASSWORD', 'users', $id);
    }

    public static function setStatus(int $id, string $status): void
    {
        $user = self::find($id);
        if ($id === AuthService::id()) {
            throw ValidationException::rule('You cannot change your own status.');
        }
        self::assertManageable($user);
        Database::run('UPDATE users SET status = ?, session_version = session_version + 1 WHERE id = ? AND company_id = ?', [$status, $id, Tenant::id()]);
        AuditLogService::log($status === 'active' ? 'ACTIVATE_USER' : 'DEACTIVATE_USER', 'users', $id, ['status' => $user['status']], ['status' => $status]);
    }

    public static function delete(int $id): void
    {
        $user = self::find($id);
        if ($id === AuthService::id()) {
            throw ValidationException::rule('You cannot delete your own account.');
        }
        self::assertManageable($user);
        // Soft delete; free unique identifiers so they can be reused.
        Database::run(
            "UPDATE users SET deleted_at = NOW(), status = 'inactive', session_version = session_version + 1,
             email = NULL, phone = NULL, username = LEFT(CONCAT('deleted_', id, '_', username), 50) WHERE id = ? AND company_id = ?",
            [$id, Tenant::id()]
        );
        AuditLogService::log('DELETE_USER', 'users', $id, ['username' => $user['username'], 'email' => $user['email']], null);
    }

    public static function loginHistory(int $id, int $limit = 20): array
    {
        return Database::fetchAll('SELECT ip, user_agent, success, created_at FROM login_attempts WHERE user_id = ? ORDER BY id DESC LIMIT ' . $limit, [$id]);
    }
}
