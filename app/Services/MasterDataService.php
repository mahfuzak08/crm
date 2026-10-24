<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\ValidationException;
use App\Core\Validator;

/**
 * Generic lookup lists stored in `master_data`.
 * Rows flagged is_system carry a fixed `code` that code relies on (e.g. lead
 * status "converted"); they can be renamed/recoloured but not deleted.
 */
final class MasterDataService
{
    /** type => [label, singular, has color] */
    public const TYPES = [
        'lead_source'      => ['Lead Sources', 'Lead Source', false],
        'lead_status'      => ['Lead Statuses', 'Lead Status', true],
        'customer_type'    => ['Customer Types', 'Customer Type', false],
        'project_status'   => ['Project Statuses', 'Project Status', true],
        'share_type'       => ['Share Types', 'Share Type', false],
        'payment_method'   => ['Payment Methods', 'Payment Method', false],
        'expense_category' => ['Expense Categories', 'Expense Category', false],
        'department'       => ['Departments', 'Department', false],
        'designation'      => ['Designations', 'Designation', false],
        'document_type'    => ['Document Types', 'Document Type', false],
        'activity_type'    => ['Activity Types', 'Activity Type', true],
    ];

    /** Default seed per company: type => [[name, code|null, color|null, is_system]] */
    public const SEED = [
        'lead_status' => [
            ['New', 'new', '#0d6efd', 1], ['Contacted', 'contacted', '#6610f2', 0], ['Interested', 'interested', '#0dcaf0', 0],
            ['Offer Sent', 'offer_sent', '#6f42c1', 1], ['Follow Up', 'follow_up', '#fd7e14', 0], ['Negotiation', 'negotiation', '#ffc107', 0],
            ['Converted', 'converted', '#198754', 1], ['Lost', 'lost', '#dc3545', 1],
        ],
        'lead_source' => [['Facebook', null, null, 0], ['Website', null, null, 0], ['Referral', null, null, 0], ['Walk-in', null, null, 0], ['Phone Call', null, null, 0], ['Event', null, null, 0], ['Other', null, null, 0]],
        'customer_type' => [['Individual', null, null, 0], ['Corporate', null, null, 0], ['NRB (Non-Resident)', null, null, 0]],
        'project_status' => [['Planning', 'planning', '#6c757d', 0], ['Ongoing', 'ongoing', '#0d6efd', 0], ['Completed', 'completed', '#198754', 0], ['On Hold', 'on_hold', '#ffc107', 0]],
        'share_type' => [['Standard', null, null, 0], ['Premium', null, null, 0], ['Corner Plot', null, null, 0]],
        'payment_method' => [['Cash', 'cash', null, 0], ['Bank', 'bank', null, 0], ['bKash', 'bkash', null, 0], ['Nagad', 'nagad', null, 0], ['Rocket', 'rocket', null, 0], ['Cheque', 'cheque', null, 0]],
        'expense_category' => [['Office Rent', null, null, 0], ['Utilities', null, null, 0], ['Marketing', null, null, 0], ['Transport', null, null, 0], ['Salary', null, null, 0], ['Maintenance', null, null, 0], ['Other', null, null, 0]],
        'department' => [['Management', null, null, 0], ['Sales', null, null, 0], ['Accounts', null, null, 0], ['Operations', null, null, 0]],
        'designation' => [['Managing Director', null, null, 0], ['Manager', null, null, 0], ['Sales Executive', null, null, 0], ['Accountant', null, null, 0], ['Office Assistant', null, null, 0]],
        'document_type' => [['Land Document', null, null, 0], ['Deed / Dolil', null, null, 0], ['Project Brochure', null, null, 0], ['Agreement', null, null, 0], ['Payment Receipt', null, null, 0], ['Customer NID', null, null, 0], ['Other', null, null, 0]],
        'activity_type' => [
            ['Call', 'call', '#0d6efd', 1], ['Meeting', 'meeting', '#6610f2', 1], ['SMS', 'sms', '#20c997', 1], ['Email', 'email', '#0dcaf0', 1],
            ['WhatsApp', 'whatsapp', '#25d366', 1], ['Follow-up', 'follow_up', '#fd7e14', 1], ['Note', 'note', '#6c757d', 1],
            ['Offer Sent', 'offer_sent', '#6f42c1', 1], ['Payment', 'payment', '#198754', 1], ['Document Shared', 'document_shared', '#795548', 1],
        ],
    ];

    public static function assertType(string $type): void
    {
        if (!isset(self::TYPES[$type])) {
            throw new HttpException(404);
        }
    }

    /** Active items for dropdowns: [id => name] (cached per request). */
    public static function options(string $type, bool $activeOnly = true): array
    {
        static $cache = [];
        $key = $type . ($activeOnly ? '1' : '0') . Tenant::id();
        return $cache[$key] ??= Database::pairs(
            'SELECT id, name FROM master_data WHERE company_id = ? AND type = ?' . ($activeOnly ? ' AND is_active = 1' : '') . ' ORDER BY sort_order, name',
            [Tenant::id(), $type]
        );
    }

    public static function all(string $type): array
    {
        return Database::fetchAll('SELECT * FROM master_data WHERE company_id = ? AND type = ? ORDER BY sort_order, name', [Tenant::id(), $type]);
    }

    public static function idByCode(string $type, string $code): ?int
    {
        $id = Database::value('SELECT id FROM master_data WHERE company_id = ? AND type = ? AND code = ?', [Tenant::id(), $type, $code]);
        return $id ? (int) $id : null;
    }

    /** Validator closure: value must be an id of $type in the active company. */
    public static function rule(string $type, bool $activeOnly = false): \Closure
    {
        return static fn($v) => Tenant::owns('master_data', $v, 'type = ?' . ($activeOnly ? ' AND is_active = 1' : ''), [$type])
            ? null : 'Please select a valid ' . strtolower(self::TYPES[$type][1]) . '.';
    }

    public static function save(string $type, array $input, ?int $id = null): int
    {
        self::assertType($type);
        $data = Validator::make($input, [
            'name'       => 'required|max:100',
            'color'      => 'nullable|color',
            'sort_order' => 'nullable|integer',
            'is_active'  => 'bool',
        ]);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        if (!self::TYPES[$type][2]) {
            $data['color'] = null;
        }
        $dup = Database::value(
            'SELECT id FROM master_data WHERE company_id = ? AND type = ? AND name = ? AND id <> ?',
            [Tenant::id(), $type, $data['name'], $id ?? 0]
        );
        if ($dup) {
            throw new ValidationException(['name' => 'This name already exists.']);
        }

        if ($id) {
            $old = Database::fetch('SELECT * FROM master_data WHERE id = ? AND company_id = ? AND type = ?', [$id, Tenant::id(), $type]);
            if (!$old) {
                throw new HttpException(404);
            }
            if ($old['is_system'] && !$data['is_active']) {
                throw ValidationException::rule('System items cannot be disabled.');
            }
            Database::update('master_data', $data, 'id = ? AND company_id = ?', [$id, Tenant::id()]);
            AuditLogService::log('UPDATE_MASTER_DATA', 'master_data', $id, $old, $data);
            return $id;
        }
        $data += ['company_id' => Tenant::id(), 'type' => $type];
        $newId = Database::insert('master_data', $data);
        AuditLogService::log('CREATE_MASTER_DATA', 'master_data', $newId, null, $data);
        return $newId;
    }

    public static function delete(string $type, int $id): void
    {
        $row = Database::fetch('SELECT * FROM master_data WHERE id = ? AND company_id = ? AND type = ?', [$id, Tenant::id(), $type]);
        if (!$row) {
            throw new HttpException(404);
        }
        if ($row['is_system']) {
            throw ValidationException::rule('System items cannot be deleted.');
        }
        try {
            Database::run('DELETE FROM master_data WHERE id = ? AND company_id = ?', [$id, Tenant::id()]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                throw ValidationException::rule('This item is in use. Disable it instead of deleting.');
            }
            throw $e;
        }
        AuditLogService::log('DELETE_MASTER_DATA', 'master_data', $id, $row, null);
    }

    public static function seed(int $companyId): void
    {
        foreach (self::SEED as $type => $items) {
            foreach ($items as $i => [$name, $code, $color, $system]) {
                Database::run(
                    'INSERT IGNORE INTO master_data (company_id, type, name, code, color, sort_order, is_active, is_system) VALUES (?, ?, ?, ?, ?, ?, 1, ?)',
                    [$companyId, $type, $name, $code, $color, ($i + 1) * 10, $system]
                );
            }
        }
    }
}
