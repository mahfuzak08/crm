<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use PDO;

/**
 * Pure-PHP SQL backup (works on shared hosting without mysqldump).
 * Files are gzip-compressed into storage/backups (outside the web root).
 * A backup contains ALL companies, so it is restricted to Super Admin.
 */
final class BackupService
{
    public const DIR = STORAGE_PATH . '/backups';

    public static function create(): string
    {
        @set_time_limit(0);
        if (!is_dir(self::DIR)) {
            mkdir(self::DIR, 0750, true);
        }
        $name = 'backup-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.sql.gz';
        $path = self::DIR . '/' . $name;
        $gz = gzopen($path, 'wb6');
        if (!$gz) {
            throw new \RuntimeException('Cannot write backup file');
        }
        $pdo = Database::pdo();
        $db = (string) config('db.database');
        gzwrite($gz, "-- Lake View CRM backup\n-- Database: $db\n-- Created: " . date('c') . "\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

        foreach ($pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(PDO::FETCH_NUM) as [$table]) {
            $qt = Database::ident($table);
            $create = $pdo->query('SHOW CREATE TABLE ' . $qt)->fetch(PDO::FETCH_NUM)[1];
            gzwrite($gz, "DROP TABLE IF EXISTS $qt;\n$create;\n\n");
            $offset = 0;
            $chunk = 500;
            do {
                $rows = $pdo->query("SELECT * FROM $qt LIMIT $chunk OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) {
                    $cols = implode(', ', array_map([Database::class, 'ident'], array_keys($rows[0])));
                    $values = [];
                    foreach ($rows as $r) {
                        $values[] = '(' . implode(', ', array_map(static fn($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string) $v : $pdo->quote((string) $v)), $r)) . ')';
                    }
                    gzwrite($gz, "INSERT INTO $qt ($cols) VALUES\n" . implode(",\n", $values) . ";\n");
                }
                $offset += $chunk;
            } while (count($rows) === $chunk);
            gzwrite($gz, "\n");
        }
        gzwrite($gz, "SET FOREIGN_KEY_CHECKS = 1;\n");
        gzclose($gz);
        @chmod($path, 0640);
        AuditLogService::log('CREATE_BACKUP', 'system', null, null, ['file' => $name, 'size' => filesize($path)]);
        return $name;
    }

    public static function list(): array
    {
        $files = glob(self::DIR . '/backup-*.sql.gz') ?: [];
        rsort($files);
        return array_map(static fn($f) => ['name' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)], $files);
    }

    /** Resolve a backup name safely (no path traversal). */
    public static function path(string $name): string
    {
        if (!preg_match('/^backup-\d{8}-\d{6}-[a-f0-9]{8}\.sql\.gz$/', $name) || !is_file(self::DIR . '/' . $name)) {
            throw new HttpException(404);
        }
        return self::DIR . '/' . $name;
    }

    public static function delete(string $name): void
    {
        unlink(self::path($name));
        AuditLogService::log('DELETE_BACKUP', 'system', null, ['file' => $name], null);
    }

    /** Keep only the newest $keep backups (used by cron). */
    public static function prune(int $keep = 14): int
    {
        $n = 0;
        foreach (array_slice(self::list(), $keep) as $b) {
            @unlink(self::DIR . '/' . $b['name']);
            $n++;
        }
        return $n;
    }
}
