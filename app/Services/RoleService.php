<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\ValidationException;
use App\Core\Validator;

/** Roles & permissions (RBAC). Permission names come from app/config/permissions.php. */
final class RoleService
{
    public static function catalog(): array
    {
        static $c = null;
        return $c ??= require APP_PATH . '/config/permissions.php';
    }

    /** Upsert permission catalog into DB. */
    public static function syncPermissions(): void
    {
        foreach (self::catalog()['modules'] as $module => $perms) {
            foreach ($perms as $name => $label) {
                Database::run(
                    'INSERT INTO permissions (name, module, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE module = VALUES(module), label = VALUES(label)',
                    [$name, $module, $label]
                );
            }
        }
    }

    /** Create the default global roles with their default permission sets. */
    public static function seedDefaultRoles(): void
    {
        $all = Database::pairs('SELECT name, id FROM permissions');
        foreach (self::catalog()['default_roles'] as $slug => $def) {
            $roleId = (int) Database::value('SELECT id FROM roles WHERE company_id IS NULL AND slug = ?', [$slug]);
            if (!$roleId) {
                $roleId = Database::insert('roles', [
                    'company_id' => null, 'name' => $def['name'], 'slug' => $slug,
                    'description' => $def['description'] ?? null, 'is_system' => 1,
                ]);
                foreach (self::expand($def['perms'], array_keys($all)) as $perm) {
                    Database::run('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)', [$roleId, $all[$perm]]);
                }
            }
        }
    }

    /** Expand patterns: '*', 'module.*', '*.view', '!perm' (exclude). */
    public static function expand(array $patterns, array $names): array
    {
        $out = [];
        foreach ($patterns as $p) {
            if (str_starts_with($p, '!')) {
                continue;
            }
            $re = '/^' . str_replace('\*', '[a-z_]+', preg_quote($p, '/')) . '$/';
            if ($p === '*') {
                $re = '/.*/';
            }
            foreach ($names as $n) {
                if (preg_match($re, $n)) {
                    $out[$n] = true;
                }
            }
        }
        foreach ($patterns as $p) {
            if (str_starts_with($p, '!')) {
                unset($out[substr($p, 1)]);
            }
        }
        return array_keys($out);
    }

    /** Roles visible in the active company (global + company-specific). */
    public static function available(bool $includeSuper = false): array
    {
        $rows = Database::fetchAll(
            'SELECT r.*, (SELECT COUNT(*) FROM user_roles ur JOIN users u ON u.id = ur.user_id WHERE ur.role_id = r.id AND u.company_id = ? AND u.deleted_at IS NULL) AS users_count
             FROM roles r WHERE r.company_id IS NULL OR r.company_id = ? ORDER BY r.is_system DESC, r.name',
            [Tenant::id(), Tenant::id()]
        );
        if (!$includeSuper) {
            $rows = array_values(array_filter($rows, static fn($r) => $r['slug'] !== 'super_admin'));
        }
        return $rows;
    }

    public static function find(int $id): array
    {
        $role = Database::fetch('SELECT * FROM roles WHERE id = ? AND (company_id IS NULL OR company_id = ?)', [$id, Tenant::id()]);
        if (!$role) {
            throw new HttpException(404);
        }
        return $role;
    }

    /** Global/system roles are shared by all companies: only Super Admin may modify them. */
    public static function assertEditable(array $role): void
    {
        if ($role['slug'] === 'super_admin') {
            throw ValidationException::rule('The Super Admin role always has full access and cannot be modified.');
        }
        if ($role['company_id'] === null && !AuthService::isSuperAdmin()) {
            throw new HttpException(403, 'System roles can only be modified by the Super Admin.');
        }
    }

    public static function save(array $input, ?int $id = null): int
    {
        $data = Validator::make($input, ['name' => 'required|max:80', 'description' => 'nullable|max:255']);
        $permIds = array_values(array_filter(array_map('intval', (array) ($input['permissions'] ?? []))));
        $validPerms = $permIds ? Database::fetchAll('SELECT id FROM permissions WHERE id IN (' . Database::placeholders($permIds) . ')', $permIds) : [];
        $permIds = array_map('intval', array_column($validPerms, 'id'));
        $names = $permIds ? array_column(Database::fetchAll('SELECT name FROM permissions WHERE id IN (' . Database::placeholders($permIds) . ')', $permIds), 'name') : [];
        AuthService::assertCanGrant($names);
        if ($id) {
            AuthService::assertCanGrant(self::permissionNamesForRoles([$id])); // cannot edit a more powerful role
        }

        return Database::transaction(static function () use ($data, $permIds, $id) {
            if ($id) {
                $role = self::find($id);
                self::assertEditable($role);
                Database::update('roles', $data, 'id = ?', [$id]);
            } else {
                $slug = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($data['name'])), '_') ?: 'role';
                if (Database::value('SELECT 1 FROM roles WHERE slug = ? AND (company_id IS NULL OR company_id = ?)', [$slug, Tenant::id()])) {
                    throw new ValidationException(['name' => 'A role with this name already exists.']);
                }
                $id = Database::insert('roles', $data + ['company_id' => Tenant::id(), 'slug' => $slug, 'is_system' => 0]);
            }
            $old = Database::fetchAll('SELECT p.name FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = ?', [$id]);
            Database::run('DELETE FROM role_permissions WHERE role_id = ?', [$id]);
            foreach ($permIds as $pid) {
                Database::run('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)', [$id, $pid]);
            }
            $new = $permIds ? Database::fetchAll('SELECT name FROM permissions WHERE id IN (' . Database::placeholders($permIds) . ')', $permIds) : [];
            AuditLogService::log('SAVE_ROLE', 'roles', $id,
                ['permissions' => implode(',', array_column($old, 'name'))],
                ['name' => $data['name'], 'permissions' => implode(',', array_column($new, 'name'))]);
            return $id;
        });
    }

    public static function delete(int $id): void
    {
        $role = self::find($id);
        if ($role['is_system']) {
            throw ValidationException::rule('System roles cannot be deleted.');
        }
        self::assertEditable($role);
        if (Database::value('SELECT 1 FROM user_roles WHERE role_id = ?', [$id])) {
            throw ValidationException::rule('This role is assigned to users. Remove it from users first.');
        }
        Database::run('DELETE FROM roles WHERE id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('DELETE_ROLE', 'roles', $id, $role, null);
    }

    /** Distinct permission names granted by a set of roles. */
    public static function permissionNamesForRoles(array $roleIds): array
    {
        if (!$roleIds) {
            return [];
        }
        return array_column(Database::fetchAll(
            'SELECT DISTINCT p.name FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id IN (' . Database::placeholders($roleIds) . ')',
            array_values($roleIds)
        ), 'name');
    }

    public static function permissionIds(int $roleId): array
    {
        return array_map('intval', array_column(Database::fetchAll('SELECT permission_id FROM role_permissions WHERE role_id = ?', [$roleId]), 'permission_id'));
    }

    public static function permissionsByModule(): array
    {
        $out = [];
        foreach (Database::fetchAll('SELECT * FROM permissions ORDER BY id') as $p) {
            $out[$p['module']][] = $p;
        }
        return $out;
    }
}
