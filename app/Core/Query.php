<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Tiny select builder for listing pages: filters, search, whitelisted sorting,
 * server-side pagination. SQL fragments (joins/conditions) are written in code;
 * every user value is bound as a parameter.
 */
final class Query
{
    private string $select = '*';
    private array $joins = [];
    private array $joinParams = [];
    private array $wheres = [];
    private array $params = [];
    private string $order = '';
    private string $group = '';

    private function __construct(private readonly string $from)
    {
    }

    public static function table(string $from): self
    {
        return new self($from);
    }

    public function select(string $columns): self
    {
        $this->select = $columns;
        return $this;
    }

    /** JOIN clause; optional params are bound before WHERE params. */
    public function join(string $join, mixed ...$params): self
    {
        $this->joins[] = $join;
        array_push($this->joinParams, ...$params);
        return $this;
    }

    public function where(string $condition, mixed ...$params): self
    {
        $this->wheres[] = '(' . $condition . ')';
        array_push($this->params, ...$params);
        return $this;
    }

    /** Add condition only when $value is non-empty. */
    public function whereIf(mixed $value, string $condition, mixed ...$params): self
    {
        if ($value !== null && $value !== '' && $value !== []) {
            $this->where($condition, ...($params ?: [$value]));
        }
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if ($values === []) {
            return $this->where('1 = 0');
        }
        return $this->where($column . ' IN (' . Database::placeholders($values) . ')', ...array_values($values));
    }

    public function dateRange(string $column, ?string $from, ?string $to): self
    {
        if ($from && is_valid_date($from)) {
            $this->where($column . ' >= ?', $from);
        }
        if ($to && is_valid_date($to)) {
            $this->where($column . ' <= ?', $to);
        }
        return $this;
    }

    /** LIKE search across columns. */
    public function search(array $columns, ?string $term): self
    {
        $term = trim((string) $term);
        if ($term === '' || $columns === []) {
            return $this;
        }
        $like = '%' . addcslashes(mb_substr($term, 0, 100), '%_\\') . '%';
        $parts = array_map(static fn($c) => $c . ' LIKE ?', $columns);
        return $this->where(implode(' OR ', $parts), ...array_fill(0, count($columns), $like));
    }

    /**
     * Sort by a whitelisted key. $allowed maps request key => SQL expression.
     */
    public function sort(?string $key, ?string $dir, array $allowed, string $default): self
    {
        $dir = strtolower((string) $dir) === 'asc' ? 'ASC' : 'DESC';
        $this->order = isset($allowed[$key]) ? $allowed[$key] . ' ' . $dir : $default;
        return $this;
    }

    public function orderBy(string $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function groupBy(string $group): self
    {
        $this->group = $group;
        return $this;
    }

    public function toSql(): string
    {
        $sql = 'SELECT ' . $this->select . ' FROM ' . $this->from;
        if ($this->joins) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        if ($this->wheres) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        if ($this->group) {
            $sql .= ' GROUP BY ' . $this->group;
        }
        if ($this->order) {
            $sql .= ' ORDER BY ' . $this->order;
        }
        return $sql;
    }

    public function params(): array
    {
        return [...$this->joinParams, ...$this->params];
    }

    public function get(?int $limit = null): array
    {
        $sql = $this->toSql() . ($limit ? ' LIMIT ' . (int) $limit : '');
        return Database::fetchAll($sql, $this->params());
    }

    public function first(): ?array
    {
        return Database::fetch($this->toSql() . ' LIMIT 1', $this->params());
    }

    public function count(): int
    {
        $inner = 'SELECT 1 FROM ' . $this->from . ($this->joins ? ' ' . implode(' ', $this->joins) : '')
            . ($this->wheres ? ' WHERE ' . implode(' AND ', $this->wheres) : '')
            . ($this->group ? ' GROUP BY ' . $this->group : '');
        return (int) Database::value('SELECT COUNT(*) FROM (' . $inner . ') t', $this->params());
    }

    /** Aggregate over the filtered set, e.g. ['total' => 'SUM(i.grand_total)']. */
    public function aggregate(array $exprs): array
    {
        $cols = [];
        foreach ($exprs as $alias => $expr) {
            $cols[] = $expr . ' AS ' . Database::ident($alias);
        }
        $sql = 'SELECT ' . implode(', ', $cols) . ' FROM ' . $this->from
            . ($this->joins ? ' ' . implode(' ', $this->joins) : '')
            . ($this->wheres ? ' WHERE ' . implode(' AND ', $this->wheres) : '');
        return Database::fetch($sql, $this->params()) ?? [];
    }

    /**
     * @return array{rows: array, total: int, page: int, per_page: int, pages: int}
     */
    public function paginate(int $page, ?int $perPage = null): array
    {
        $perPage = max(1, min(200, $perPage ?? (int) config('per_page', 20)));
        $total = $this->count();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;
        $rows = Database::fetchAll($this->toSql() . ' LIMIT ' . $perPage . ' OFFSET ' . $offset, $this->params());
        return compact('rows', 'total', 'page', 'perPage', 'pages') + ['per_page' => $perPage];
    }

    /** Stream all rows (for CSV export) without loading into memory. */
    public function cursor(): \Generator
    {
        $stmt = Database::run($this->toSql(), $this->params());
        while ($row = $stmt->fetch()) {
            yield $row;
        }
    }
}
