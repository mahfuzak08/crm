<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/** Requirements check, .env writer, migration runner, initial seed. */
final class InstallService
{
    public const LOCK_FILE = STORAGE_PATH . '/installed.lock';

    public static function isInstalled(): bool
    {
        return is_file(self::LOCK_FILE);
    }

    public static function requirements(): array
    {
        $ext = ['pdo_mysql', 'mbstring', 'fileinfo', 'openssl', 'json', 'gd'];
        // [label, ok, blocking]
        $checks = [['PHP >= 8.2 (current ' . PHP_VERSION . ')', version_compare(PHP_VERSION, '8.2.0', '>='), true]];
        foreach ($ext as $e) {
            $checks[] = ['Extension: ' . $e, extension_loaded($e), true];
        }
        foreach (['storage', 'storage/logs', 'storage/private', 'storage/backups'] as $d) {
            $checks[] = ['Writable: ' . $d, is_writable(BASE_PATH . '/' . $d), true];
        }
        $checks[] = ['Project root writable for .env (otherwise create it manually)', is_writable(BASE_PATH) || is_file(BASE_PATH . '/.env'), false];
        return $checks;
    }

    public static function writeEnv(array $db, string $appUrl): void
    {
        $file = BASE_PATH . '/.env';
        $tpl = is_file(BASE_PATH . '/.env.example') ? file_get_contents(BASE_PATH . '/.env.example') : '';
        $vals = [
            'APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_URL' => $appUrl,
            'APP_KEY' => bin2hex(random_bytes(32)),
            'DB_HOST' => $db['host'], 'DB_PORT' => (string) $db['port'], 'DB_DATABASE' => $db['database'],
            'DB_USERNAME' => $db['username'], 'DB_PASSWORD' => $db['password'],
        ];
        foreach ($vals as $k => $v) {
            $line = $k . '="' . addcslashes($v, "\"\\\$") . '"';
            $tpl = preg_match('/^' . $k . '=.*$/m', $tpl)
                ? preg_replace('/^' . $k . '=.*$/m', str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $tpl)
                : $tpl . PHP_EOL . $line;
        }
        if (file_put_contents($file, $tpl, LOCK_EX) === false) {
            throw new \RuntimeException('Could not write .env');
        }
        @chmod($file, 0640);
    }

    /** Apply pending SQL migrations in order. Returns list of applied files. */
    public static function migrate(PDO $pdo): array
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (migration VARCHAR(100) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $done = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        $files = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
        sort($files);
        $applied = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $done, true)) {
                continue;
            }
            foreach (self::splitSql((string) file_get_contents($file)) as $stmt) {
                $pdo->exec($stmt);
            }
            $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)')->execute([$name]);
            $applied[] = $name;
        }
        return $applied;
    }

    /** Split a SQL file into statements (no procedures/triggers are used). */
    public static function splitSql(string $sql): array
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        return array_values(array_filter(array_map('trim', explode(';', (string) $sql)), static fn($s) => $s !== ''));
    }

    /** Seed system data, initial company and Super Admin. */
    public static function seed(array $company, array $admin): void
    {
        Database::transaction(static function () use ($company, $admin) {
            RoleService::syncPermissions();
            RoleService::seedDefaultRoles();
            $companyId = Database::insert('companies', $company);
            CompanyService::seedDefaults($companyId);

            $userId = Database::insert('users', [
                'company_id' => $companyId,
                'name' => $admin['name'], 'email' => $admin['email'], 'phone' => $admin['phone'],
                'username' => $admin['username'],
                'password' => password_hash($admin['password'], PASSWORD_DEFAULT),
                'status' => 'active', 'password_changed_at' => now(),
            ]);
            $superRole = (int) Database::value("SELECT id FROM roles WHERE slug = 'super_admin' AND company_id IS NULL");
            Database::insert('user_roles', ['user_id' => $userId, 'role_id' => $superRole]);
            AuditLogService::log('INSTALL', 'system', $userId, null, ['company' => $company['name'], 'admin' => $admin['username']], $companyId);
        });
    }

    public static function lock(): void
    {
        file_put_contents(self::LOCK_FILE, 'Installed at ' . date('c') . PHP_EOL, LOCK_EX);
    }
}
