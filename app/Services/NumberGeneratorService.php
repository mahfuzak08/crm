<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Concurrency-safe document numbers: CUS-000001, INV-2026-000001 ...
 * Locks the sequence row (SELECT ... FOR UPDATE) inside the caller's transaction.
 */
final class NumberGeneratorService
{
    /** key => [label, default prefix, yearly reset, table, column] */
    public const SEQUENCES = [
        'customer' => ['Customer',       'CUS',  0, 'customers',       'customer_code'],
        'lead'     => ['Lead',           'LEAD', 0, 'leads',           'lead_code'],
        'employee' => ['Employee',       'EMP',  0, 'employees',       'employee_code'],
        'project'  => ['Project',        'PRJ',  0, 'projects',        'project_code'],
        'offer'    => ['Offer',          'OFF',  1, 'offers',          'offer_number'],
        'sale'     => ['Share Sale',     'SALE', 1, 'share_sales',     'sale_number'],
        'invoice'  => ['Invoice',        'INV',  1, 'invoices',        'invoice_number'],
        'payment'  => ['Payment/Receipt','PAY',  1, 'payments',        'payment_number'],
        'expense'  => ['Expense',        'EXP',  1, 'expenses',        'expense_number'],
        'salary'   => ['Salary Payment', 'SAL',  1, 'salary_payments', 'payment_number'],
        'fund'     => ['Fund Contribution', 'FND', 1, 'fund_contributions', 'fund_number'],
    ];

    public static function next(string $key, ?int $companyId = null): string
    {
        if (!isset(self::SEQUENCES[$key])) {
            throw new \InvalidArgumentException('Unknown sequence');
        }
        $cid = $companyId ?? Tenant::id();

        return Database::transaction(static function () use ($key, $cid) {
            $row = Database::fetch('SELECT * FROM number_sequences WHERE company_id = ? AND seq_key = ? FOR UPDATE', [$cid, $key]);
            if (!$row) {
                self::ensure($cid);
                $row = Database::fetch('SELECT * FROM number_sequences WHERE company_id = ? AND seq_key = ? FOR UPDATE', [$cid, $key]);
            }
            $year = (int) date('Y');
            $n = (int) $row['next_number'];
            if ((int) $row['yearly_reset'] === 1 && (int) $row['current_year'] !== $year) {
                $n = 1;
            }
            [, , , $table, $col] = self::SEQUENCES[$key];
            // Skip numbers already taken (e.g. starting number lowered in setup).
            for ($guard = 0; $guard < 1000; $guard++) {
                $number = self::format($row, $n, $year);
                $exists = Database::value('SELECT 1 FROM ' . Database::ident($table) . ' WHERE company_id = ? AND ' . Database::ident($col) . ' = ?', [$cid, $number]);
                if (!$exists) {
                    break;
                }
                $n++;
            }
            Database::run(
                'UPDATE number_sequences SET next_number = ?, current_year = ? WHERE company_id = ? AND seq_key = ?',
                [$n + 1, $year, $cid, $key]
            );
            return $number;
        });
    }

    public static function format(array $seq, int $n, ?int $year = null): string
    {
        $num = str_pad((string) $n, (int) $seq['number_length'], '0', STR_PAD_LEFT);
        return (int) $seq['yearly_reset'] === 1
            ? sprintf('%s-%d-%s', $seq['prefix'], $year ?? (int) date('Y'), $num)
            : sprintf('%s-%s', $seq['prefix'], $num);
    }

    /** Create default sequence rows for a company (idempotent). */
    public static function ensure(int $companyId): void
    {
        foreach (self::SEQUENCES as $key => [$label, $prefix, $yearly]) {
            Database::run(
                'INSERT IGNORE INTO number_sequences (company_id, seq_key, prefix, next_number, number_length, yearly_reset, current_year) VALUES (?, ?, ?, 1, 6, ?, ?)',
                [$companyId, $key, $prefix, $yearly, (int) date('Y')]
            );
        }
    }

    public static function preview(string $key): string
    {
        $row = Database::fetch('SELECT * FROM number_sequences WHERE company_id = ? AND seq_key = ?', [Tenant::id(), $key]);
        return $row ? self::format($row, (int) $row['next_number']) : '';
    }
}
