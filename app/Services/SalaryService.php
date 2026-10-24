<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Money;
use App\Core\Query;
use App\Core\ValidationException;
use App\Core\Validator;

/**
 * Salary: dynamic components (allowance / deduction / bonus / commission; fixed or % of basic),
 * per-employee structure overrides, monthly salary payments (one per employee per month).
 */
final class SalaryService
{
    public const TYPES = ['allowance' => 'Allowance', 'deduction' => 'Deduction', 'bonus' => 'Bonus', 'commission' => 'Commission'];

    public const SEED_COMPONENTS = [
        ['House Rent', 'allowance', 'percent', '40.00'],
        ['Transport', 'allowance', 'fixed', '1500.00'],
        ['Medical', 'allowance', 'fixed', '1000.00'],
        ['Mobile Bill', 'allowance', 'fixed', '500.00'],
        ['Festival Bonus', 'bonus', 'fixed', '0.00'],
        ['Sales Commission', 'commission', 'fixed', '0.00'],
        ['Tax', 'deduction', 'fixed', '0.00'],
        ['Other Deduction', 'deduction', 'fixed', '0.00'],
    ];

    public static function seedComponents(int $companyId): void
    {
        foreach (self::SEED_COMPONENTS as $i => [$name, $type, $calc, $value]) {
            Database::run(
                'INSERT IGNORE INTO salary_components (company_id, name, type, calc_type, default_value, sort_order) VALUES (?, ?, ?, ?, ?, ?)',
                [$companyId, $name, $type, $calc, $value, ($i + 1) * 10]
            );
        }
    }

    /* ---------------- components ---------------- */

    public static function components(bool $activeOnly = false): array
    {
        return Database::fetchAll('SELECT * FROM salary_components WHERE company_id = ?' . ($activeOnly ? ' AND is_active = 1' : '') . ' ORDER BY sort_order, name', [Tenant::id()]);
    }

    public static function saveComponent(array $input, ?int $id = null): int
    {
        $old = $id ? (Database::fetch('SELECT * FROM salary_components WHERE id = ? AND company_id = ?', [$id, Tenant::id()]) ?? throw new HttpException(404)) : null;
        $data = Validator::make($input, [
            'name'          => 'required|max:100',
            'type'          => 'required|in:allowance,deduction,bonus,commission',
            'calc_type'     => 'required|in:fixed,percent',
            'default_value' => 'required|money',
            'sort_order'    => 'nullable|integer',
            'is_active'     => 'bool',
        ], ['default_value' => 'Default value']);
        if ($data['calc_type'] === 'percent' && Money::toCents($data['default_value']) > 10000) {
            throw new ValidationException(['default_value' => 'Percentage cannot exceed 100.']);
        }
        $data['sort_order'] ??= 0;
        if (Database::value('SELECT 1 FROM salary_components WHERE company_id = ? AND name = ? AND id <> ?', [Tenant::id(), $data['name'], $id ?? 0])) {
            throw new ValidationException(['name' => 'A component with this name exists.']);
        }
        if ($id) {
            Database::update('salary_components', $data, 'id = ? AND company_id = ?', [$id, Tenant::id()]);
            AuditLogService::log('UPDATE_SALARY_COMPONENT', 'salary', $id, $old, $data);
            return $id;
        }
        $newId = Database::insert('salary_components', $data + ['company_id' => Tenant::id()]);
        AuditLogService::log('CREATE_SALARY_COMPONENT', 'salary', $newId, null, $data);
        return $newId;
    }

    public static function deleteComponent(int $id): void
    {
        $row = Database::fetch('SELECT * FROM salary_components WHERE id = ? AND company_id = ?', [$id, Tenant::id()]) ?? throw new HttpException(404);
        Database::run('DELETE FROM salary_components WHERE id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('DELETE_SALARY_COMPONENT', 'salary', $id, $row, null);
    }

    /* ---------------- employee structure ---------------- */

    /** [component_id => value] overrides for an employee. */
    public static function structure(int $employeeId): array
    {
        return Database::pairs('SELECT component_id, value FROM employee_salary WHERE employee_id = ?', [$employeeId]);
    }

    /** $values = [component_id => "amount or %"]; blank removes the override (default applies). */
    public static function saveStructure(int $employeeId, array $values): void
    {
        $valid = array_column(self::components(), null, 'id');
        Database::run('DELETE FROM employee_salary WHERE employee_id = ?', [$employeeId]);
        foreach ($values as $cid => $v) {
            $v = trim((string) $v);
            if ($v === '' || !isset($valid[(int) $cid])) {
                continue;
            }
            if (!Money::isValid($v)) {
                throw new ValidationException(['component' => 'Salary component values must be valid amounts.']);
            }
            Database::insert('employee_salary', ['employee_id' => $employeeId, 'component_id' => (int) $cid, 'value' => Money::normalize($v)]);
        }
    }

    /** Compute monthly salary breakdown for an employee (all integer cents). */
    public static function compute(array $employee, array $adjust = []): array
    {
        $basic = Money::toCents($employee['salary']);
        $over = self::structure((int) $employee['id']);
        $lines = [];
        $sum = ['allowance' => 0, 'deduction' => 0, 'bonus' => 0, 'commission' => 0];
        foreach (self::components(true) as $c) {
            $value = $over[$c['id']] ?? $c['default_value'];
            $amount = $c['calc_type'] === 'percent' ? Money::percent($basic, Money::normalize((string) $value)) : Money::toCents((string) $value);
            if ($amount === 0) {
                continue;
            }
            $sum[$c['type']] += $amount;
            $lines[] = ['name' => $c['name'], 'type' => $c['type'], 'amount' => Money::fromCents($amount)];
        }
        foreach (['bonus' => 'Additional bonus', 'commission' => 'Additional commission', 'deduction' => 'Additional deduction'] as $type => $label) {
            $extra = Money::toCents($adjust['extra_' . $type] ?? '0');
            if ($extra > 0) {
                $sum[$type] += $extra;
                $lines[] = ['name' => $label, 'type' => $type, 'amount' => Money::fromCents($extra)];
            }
        }
        $net = $basic + $sum['allowance'] + $sum['bonus'] + $sum['commission'] - $sum['deduction'];
        return [
            'basic' => Money::fromCents($basic), 'allowances' => Money::fromCents($sum['allowance']), 'bonus' => Money::fromCents($sum['bonus']),
            'commission' => Money::fromCents($sum['commission']), 'deductions' => Money::fromCents($sum['deduction']),
            'net_salary' => Money::fromCents($net), 'lines' => $lines, 'net_cents' => $net,
        ];
    }

    /* ---------------- payments ---------------- */

    public static function query(array $f): Query
    {
        return Query::table('salary_payments sp')
            ->select('sp.*, e.name AS employee_name, e.employee_code, d.name AS department, m.name AS method_name')
            ->join('JOIN employees e ON e.id = sp.employee_id')
            ->join('LEFT JOIN master_data d ON d.id = e.department_id')
            ->join('LEFT JOIN master_data m ON m.id = sp.payment_method_id')
            ->where('sp.company_id = ?', Tenant::id())
            ->search(['e.name', 'e.employee_code', 'sp.payment_number'], $f['q'] ?? '')
            ->whereIf($f['month'] ?? '', 'sp.salary_month = ?')
            ->whereIf($f['employee'] ?? '', 'sp.employee_id = ?', (int) ($f['employee'] ?? 0))
            ->whereIf($f['status'] ?? '', 'sp.status = ?')
            ->orderBy('sp.salary_month DESC, e.name');
    }

    public static function pay(array $input): int
    {
        $data = Validator::make($input, [
            'employee_id'       => ['required', static fn($v) => Tenant::owns('employees', $v, "deleted_at IS NULL AND status = 'active'") ? null : 'Select an active employee.'],
            'salary_month'      => 'required|month',
            'payment_date'      => 'required|date',
            'payment_method_id' => ['nullable', MasterDataService::rule('payment_method', true)],
            'reference'         => 'nullable|max:150',
            'notes'             => 'nullable|max:2000',
            'extra_bonus'       => 'nullable|money',
            'extra_commission'  => 'nullable|money',
            'extra_deduction'   => 'nullable|money',
        ], ['employee_id' => 'Employee', 'salary_month' => 'Salary month', 'payment_method_id' => 'Payment method']);

        return Database::transaction(static function () use ($data) {
            $emp = Database::fetch('SELECT * FROM employees WHERE id = ? AND company_id = ? FOR UPDATE', [$data['employee_id'], Tenant::id()]);
            if (Database::value("SELECT 1 FROM salary_payments WHERE employee_id = ? AND salary_month = ? AND status = 'paid'", [$emp['id'], $data['salary_month']])) {
                throw new ValidationException(['salary_month' => 'Salary for this month has already been paid to this employee.']);
            }
            $calc = self::compute($emp, $data);
            if ($calc['net_cents'] < 0) {
                throw ValidationException::rule('Deductions exceed the gross salary.');
            }
            $row = [
                'company_id' => Tenant::id(), 'payment_number' => NumberGeneratorService::next('salary'), 'employee_id' => $emp['id'],
                'salary_month' => $data['salary_month'], 'basic' => $calc['basic'], 'allowances' => $calc['allowances'], 'bonus' => $calc['bonus'],
                'commission' => $calc['commission'], 'deductions' => $calc['deductions'], 'net_salary' => $calc['net_salary'],
                'details' => json_encode($calc['lines'], JSON_UNESCAPED_UNICODE), 'payment_date' => $data['payment_date'],
                'payment_method_id' => $data['payment_method_id'], 'reference' => $data['reference'], 'notes' => $data['notes'],
                'status' => 'paid', 'created_by' => AuthService::id(),
            ];
            $id = Database::insert('salary_payments', $row);
            AuditLogService::log('PAY_SALARY', 'salary', $id, null, $row);
            return $id;
        });
    }

    public static function find(int $id): array
    {
        $p = Database::fetch(
            'SELECT sp.*, e.name AS employee_name, e.employee_code, e.phone, d.name AS department, g.name AS designation, m.name AS method_name, u.name AS created_by_name
             FROM salary_payments sp JOIN employees e ON e.id = sp.employee_id LEFT JOIN master_data d ON d.id = e.department_id
             LEFT JOIN master_data g ON g.id = e.designation_id LEFT JOIN master_data m ON m.id = sp.payment_method_id LEFT JOIN users u ON u.id = sp.created_by
             WHERE sp.id = ? AND sp.company_id = ?',
            [$id, Tenant::id()]
        );
        if (!$p) {
            throw new HttpException(404);
        }
        $p['lines'] = json_decode((string) $p['details'], true) ?: [];
        return $p;
    }

    public static function cancel(int $id): void
    {
        $p = self::find($id);
        if ($p['status'] === 'cancelled') {
            throw ValidationException::rule('Already cancelled.');
        }
        Database::update('salary_payments', ['status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => AuthService::id()], 'id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('CANCEL_SALARY', 'salary', $id, ['status' => 'paid'], ['status' => 'cancelled']);
    }
}
