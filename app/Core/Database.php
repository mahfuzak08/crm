<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use Throwable;

/**
 * Thin PDO wrapper. All values go through prepared statements.
 * Table/column identifiers passed to insert()/update() must come from code,
 * never from user input; they are additionally validated by ident().
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static int $txDepth = 0;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $c = config('db');
            self::$pdo = self::connect($c['host'], (int) $c['port'], $c['database'], $c['username'], $c['password']);
        }
        return self::$pdo;
    }

    public static function connect(string $host, int $port, string $db, string $user, string $pass): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;%scharset=utf8mb4', $host, $port, $db !== '' ? 'dbname=' . $db . ';' : '');
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
        // READ COMMITTED: after a FOR UPDATE lock is acquired, later reads in the
        // transaction see rows committed by the previous lock holder (no stale snapshot).
        $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $offset = (new \DateTime())->format('P');
        $pdo->exec("SET time_zone = " . $pdo->quote($offset));
        return $pdo;
    }

    public static function setConnection(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $i = 0;
        foreach ($params as $key => $value) {
            $name = is_int($key) ? ++$i : (str_starts_with($key, ':') ? $key : ':' . $key);
            $type = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $stmt->bindValue($name, $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** [key => value] pairs from first two selected columns. */
    public static function pairs(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_map([self::class, 'ident'], array_keys($data));
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::ident($table),
            implode(', ', $cols),
            implode(', ', array_fill(0, count($cols), '?'))
        );
        self::run($sql, array_values($data));
        return (int) self::pdo()->lastInsertId();
    }

    /** UPDATE table SET data WHERE $where (with its own bound params). Returns affected rows. */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        if ($data === []) {
            return 0;
        }
        $sets = [];
        foreach (array_keys($data) as $col) {
            $sets[] = self::ident($col) . ' = ?';
        }
        $sql = sprintf('UPDATE %s SET %s WHERE %s', self::ident($table), implode(', ', $sets), $where);
        return self::run($sql, [...array_values($data), ...$whereParams])->rowCount();
    }

    /**
     * Run $fn inside a transaction. Nested calls join the outer transaction.
     * @template T
     * @param callable():T $fn
     * @return T
     */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        if (self::$txDepth === 0) {
            $pdo->beginTransaction();
        }
        self::$txDepth++;
        try {
            $result = $fn();
            self::$txDepth--;
            if (self::$txDepth === 0) {
                $pdo->commit();
            }
            return $result;
        } catch (Throwable $e) {
            self::$txDepth--;
            if (self::$txDepth === 0 && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function inTransaction(): bool
    {
        return self::$txDepth > 0;
    }

    /** Validate and quote an identifier (table or column, optionally alias-qualified). */
    public static function ident(string $name): string
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*(\.[a-z_][a-z0-9_]*)?$/i', $name)) {
            throw new \InvalidArgumentException('Invalid identifier');
        }
        return implode('.', array_map(static fn($p) => '`' . $p . '`', explode('.', $name)));
    }

    /** Build "?, ?, ?" for IN() lists. */
    public static function placeholders(array $values): string
    {
        return $values === [] ? 'NULL' : implode(', ', array_fill(0, count($values), '?'));
    }
}
