<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\ValidationException;
use App\Core\Validator;

/**
 * Board of directors. A director is an existing customer or employee (or a user carried
 * over from the Director role); name/phone/email are read live from that record.
 */
final class DirectorService
{
    /** Live identity: linked customer, then employee, then user, then the stored snapshot. */
    private const SELECT = "d.*, COALESCE(c.name, e.name, u.name, d.name) AS name, COALESCE(c.phone, e.phone, u.phone, d.phone) AS phone,
        COALESCE(c.email, e.email, u.email, d.email) AS email, c.customer_code, e.employee_code";
    private const JOINS = 'LEFT JOIN customers c ON c.id = d.customer_id AND c.deleted_at IS NULL
        LEFT JOIN employees e ON e.id = d.employee_id AND e.deleted_at IS NULL LEFT JOIN users u ON u.id = d.user_id AND u.deleted_at IS NULL';

    /** Directors with their own shares and referral totals (confirmed sales). */
    public static function all(array $cids, bool $activeOnly = false): array
    {
        $in = Database::placeholders($cids);
        return Database::fetchAll(
            'SELECT ' . self::SELECT . ",
                COALESCE((SELECT SUM(ss.shares) FROM share_sales ss WHERE ss.customer_id = d.customer_id AND ss.status = 'confirmed'), 0) AS own_shares,
                COALESCE((SELECT SUM(ss.shares) FROM share_sales ss WHERE ss.director_id = d.id AND ss.status = 'confirmed'), 0) AS referred_shares,
                COALESCE((SELECT SUM(ss.director_profit) FROM share_sales ss WHERE ss.director_id = d.id AND ss.status = 'confirmed'), 0) AS referral_profit
             FROM directors d " . self::JOINS . "
             WHERE d.company_id IN ($in)" . ($activeOnly ? " AND d.status = 'active'" : '') . '
             ORDER BY d.status, d.sort_order, COALESCE(c.name, e.name, u.name, d.name)',
            $cids
        );
    }

    public static function find(int $id): array
    {
        $d = Database::fetch('SELECT ' . self::SELECT . ' FROM directors d ' . self::JOINS . ' WHERE d.id = ? AND d.company_id = ?', [$id, Tenant::id()]);
        if (!$d) {
            throw new HttpException(404);
        }
        return $d;
    }

    /** Active directors for dropdowns: [id => name]. */
    public static function options(): array
    {
        return array_column(self::all([Tenant::id()], true), 'name', 'id');
    }

    /** Active director id → customer id, to stop a director "referring" a sale to himself. */
    public static function customerIds(): array
    {
        return Database::pairs("SELECT id, customer_id FROM directors WHERE company_id = ? AND status = 'active'", [Tenant::id()]);
    }

    public static function rule(): \Closure
    {
        return static fn($v) => Tenant::owns('directors', $v, "status = 'active'") ? null : 'Please select an active director.';
    }

    /** Director linked to a customer / employee record, if any (for "Make director" buttons). */
    public static function forRecord(string $field, int $id): ?array
    {
        $field = $field === 'employee_id' ? 'employee_id' : 'customer_id';
        return Database::fetch("SELECT id, status FROM directors WHERE company_id = ? AND $field = ?", [Tenant::id(), $id]);
    }

    public static function save(array $input, ?int $id = null): int
    {
        $old = $id ? self::find($id) : null;
        $data = Validator::make($input, [
            'source'      => $id ? 'nullable|in:customer,employee' : 'required|in:customer,employee',
            'customer_id' => ['nullable', CustomerService::rule()],
            'employee_id' => ['nullable', static fn($v) => Tenant::owns('employees', $v, 'deleted_at IS NULL') ? null : 'Please select a valid employee.'],
            'designation' => 'nullable|max:80',
            'joined_on'   => 'nullable|date',
            'sort_order'  => 'nullable|integer',
            'status'      => 'required|in:active,inactive',
            'notes'       => 'nullable|max:2000',
        ], ['customer_id' => 'Customer', 'employee_id' => 'Employee']);

        $row = [
            'designation' => $data['designation'], 'joined_on' => $data['joined_on'], 'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status' => $data['status'], 'notes' => $data['notes'],
        ];
        // The person is chosen on create; afterwards a missing customer/employee link may still be added.
        $source = $data['source'] ?? null;
        $field = $source === 'employee' ? 'employee_id' : 'customer_id';
        if (!$id || ($source && $data[$field])) {
            if (!$data[$field]) {
                throw new ValidationException([$field => 'Select the ' . ($source ?? 'customer') . ' to make director.']);
            }
            if ($id && $old[$field] && (int) $old[$field] !== (int) $data[$field]) {
                throw new ValidationException([$field => 'This director is already linked to another ' . ($source ?? 'customer') . '.']);
            }
            if (Database::value("SELECT 1 FROM directors WHERE company_id = ? AND $field = ? AND id <> ?", [Tenant::id(), $data[$field], $id ?? 0])) {
                throw new ValidationException([$field => 'This person is already a director.']);
            }
            $row[$field] = (int) $data[$field];
            $person = Database::fetch('SELECT name, phone, email FROM ' . ($field === 'employee_id' ? 'employees' : 'customers') . ' WHERE id = ?', [$row[$field]]);
            if (!$id) {
                $row += $person;
            }
        }

        return Database::transaction(static function () use ($row, $id, $old) {
            if ($id) {
                Database::update('directors', $row, 'id = ? AND company_id = ?', [$id, Tenant::id()]);
                AuditLogService::log('UPDATE_DIRECTOR', 'directors', $id, array_intersect_key($old, $row), $row);
                return $id;
            }
            $row += ['company_id' => Tenant::id(), 'created_by' => AuthService::id()];
            $newId = Database::insert('directors', $row);
            AuditLogService::log('CREATE_DIRECTOR', 'directors', $newId, null, $row);
            return $newId;
        });
    }

    /** Remove from the board. Directors with referral sales are kept (set inactive instead). */
    public static function delete(int $id): void
    {
        $d = self::find($id);
        if (Database::value('SELECT 1 FROM share_sales WHERE director_id = ? LIMIT 1', [$id])) {
            throw ValidationException::rule('This director has referred share sales and cannot be removed. Set the status to inactive instead.');
        }
        Database::run('DELETE FROM directors WHERE id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('DELETE_DIRECTOR', 'directors', $id, $d, null);
    }

    /** Sales this director referred, with the office and customer prices side by side. */
    public static function referrals(int $id): array
    {
        return Database::fetchAll(
            'SELECT s.id, s.sale_number, s.sale_date, s.shares, s.grand_total, s.customer_total, s.director_profit, s.due_amount, s.status,
                    c.id AS customer_id, c.name AS customer_name, p.project_name
             FROM share_sales s JOIN customers c ON c.id = s.customer_id JOIN projects p ON p.id = s.project_id
             WHERE s.director_id = ? AND s.company_id = ? ORDER BY s.sale_date DESC, s.id DESC',
            [$id, Tenant::id()]
        );
    }

    /** The director's own purchases (as a customer). */
    public static function ownPurchases(array $d): array
    {
        if (!$d['customer_id']) {
            return [];
        }
        return Database::fetchAll(
            'SELECT s.id, s.sale_number, s.sale_date, s.shares, s.grand_total, s.due_amount, s.status, p.project_name
             FROM share_sales s JOIN projects p ON p.id = s.project_id WHERE s.customer_id = ? AND s.company_id = ? ORDER BY s.sale_date DESC, s.id DESC',
            [$d['customer_id'], Tenant::id()]
        );
    }
}
