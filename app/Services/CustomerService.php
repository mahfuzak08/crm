<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Query;
use App\Core\Request;
use App\Core\ValidationException;
use App\Core\Validator;

final class CustomerService
{
    public const FILE_FIELDS = ['photo' => 'Photo', 'nid_front' => 'NID (front)', 'nid_back' => 'NID (back)'];

    public static function query(array $f): Query
    {
        return Query::table('customers c')
            ->select("c.id, c.customer_code, c.name, c.phone, c.email, c.city, c.district, c.status, c.created_at, ct.name AS type_name,
                COALESCE((SELECT SUM(i.due_amount) FROM invoices i WHERE i.customer_id = c.id AND i.status NOT IN ('cancelled', 'draft') AND i.deleted_at IS NULL), 0) AS due,
                COALESCE((SELECT SUM(ss.shares) FROM share_sales ss WHERE ss.customer_id = c.id AND ss.status = 'confirmed'), 0) AS shares")
            ->join('LEFT JOIN master_data ct ON ct.id = c.customer_type_id')
            ->where('c.company_id = ?', Tenant::id())
            ->where('c.deleted_at IS NULL')
            ->search(['c.name', 'c.phone', 'c.email', 'c.customer_code', 'c.nid'], $f['q'] ?? '')
            ->whereIf($f['status'] ?? '', 'c.status = ?')
            ->whereIf($f['type'] ?? '', 'c.customer_type_id = ?', (int) ($f['type'] ?? 0))
            ->dateRange('DATE(c.created_at)', $f['from'] ?? null, $f['to'] ?? null)
            ->sort($f['sort'] ?? null, $f['dir'] ?? null, ['name' => 'c.name', 'code' => 'c.id', 'created' => 'c.created_at', 'due' => 'due'], 'c.id DESC');
    }

    public static function find(int $id): array
    {
        $c = Tenant::findOrFail('customers', $id);
        $c['type_name'] = $c['customer_type_id'] ? (MasterDataService::options('customer_type', false)[$c['customer_type_id']] ?? null) : null;
        return $c;
    }

    public static function rule(): \Closure
    {
        return static fn($v) => Tenant::owns('customers', $v, 'deleted_at IS NULL') ? null : 'Please select a valid customer.';
    }

    public static function validate(array $input, ?int $id = null): array
    {
        $data = Validator::make($input, [
            'name'             => 'required|max:150',
            'customer_type_id' => ['nullable', MasterDataService::rule('customer_type')],
            'father_name'      => 'nullable|max:150',
            'mother_name'      => 'nullable|max:150',
            'spouse_name'      => 'nullable|max:150',
            'phone'            => 'nullable|phone',
            'alternate_phone'  => 'nullable|phone',
            'email'            => 'nullable|email',
            'nid'              => ['nullable', 'max:30', static fn($v) => preg_match('/^[0-9A-Za-z\-]{5,30}$/', $v) ? null : 'Invalid NID number.'],
            'passport'         => 'nullable|max:30',
            'date_of_birth'    => 'nullable|date',
            'occupation'       => 'nullable|max:100',
            'address'          => 'nullable|max:1000',
            'city'             => 'nullable|max:80',
            'district'         => 'nullable|max:80',
            'country'          => 'nullable|max:80',
            'notes'            => 'nullable|max:5000',
            'status'           => 'nullable|in:active,inactive',
        ], ['customer_type_id' => 'Customer type', 'nid' => 'NID']);
        $data['status'] ??= 'active';
        if (!$data['phone'] && !$data['email']) {
            throw new ValidationException(['phone' => 'Provide a phone number or an email.']);
        }
        if ($data['nid'] && Database::value('SELECT 1 FROM customers WHERE company_id = ? AND nid = ? AND id <> ? AND deleted_at IS NULL', [Tenant::id(), $data['nid'], $id ?? 0])) {
            throw new ValidationException(['nid' => 'Another customer already has this NID.']);
        }
        return $data;
    }

    public static function save(array $input, ?int $id = null): int
    {
        $old = $id ? self::find($id) : null;
        $data = self::validate($input, $id);

        // Uploads (stored privately; served via access-controlled route).
        $newFiles = [];
        foreach (array_keys(self::FILE_FIELDS) as $field) {
            if ($file = Request::file($field)) {
                $newFiles[$field] = FileUploadService::store($file, ['image'], $field, 5)['stored_name'];
            } elseif (!empty($input['remove_' . $field]) && $old) {
                $newFiles[$field] = null;
            }
        }
        $data += $newFiles;

        $savedId = Database::transaction(static function () use ($data, $id, $old) {
            if ($id) {
                Database::update('customers', $data, 'id = ? AND company_id = ?', [$id, Tenant::id()]);
                AuditLogService::log('UPDATE_CUSTOMER', 'customers', $id, $old, $data);
                return $id;
            }
            $data += ['company_id' => Tenant::id(), 'customer_code' => NumberGeneratorService::next('customer'), 'created_by' => AuthService::id()];
            $newId = Database::insert('customers', $data);
            AuditLogService::log('CREATE_CUSTOMER', 'customers', $newId, null, $data);
            return $newId;
        });
        if ($old) {
            foreach ($newFiles as $field => $_) {
                if ($old[$field] && $old[$field] !== ($data[$field] ?? null)) {
                    FileUploadService::delete($old[$field]);
                }
            }
        }
        return $savedId;
    }

    public static function createFromLead(array $lead): int
    {
        $data = [
            'company_id' => Tenant::id(), 'customer_code' => NumberGeneratorService::next('customer'),
            'name' => $lead['name'], 'phone' => $lead['phone'], 'email' => $lead['email'], 'address' => $lead['address'],
            'notes' => $lead['notes'], 'status' => 'active', 'lead_id' => $lead['id'], 'created_by' => AuthService::id(),
        ];
        $id = Database::insert('customers', $data);
        AuditLogService::log('CREATE_CUSTOMER', 'customers', $id, null, $data + ['from_lead' => $lead['lead_code']]);
        return $id;
    }

    /**
     * Ensure a user holding the Customer role has a customer record (linked via customers.user_id).
     * Re-uses an unlinked customer with the same phone/email instead of creating a duplicate.
     */
    public static function syncFromUser(int $userId, int $companyId): int
    {
        $u = Database::fetch('SELECT id, name, email, phone FROM users WHERE id = ? AND company_id = ? AND deleted_at IS NULL', [$userId, $companyId]);
        if (!$u) {
            return 0;
        }
        $contact = array_filter(['name' => $u['name'], 'phone' => $u['phone'], 'email' => $u['email']], static fn($v) => $v !== null && $v !== '');

        $linked = Database::fetch('SELECT * FROM customers WHERE company_id = ? AND user_id = ? AND deleted_at IS NULL', [$companyId, $userId]);
        if ($linked) {
            $changes = array_diff_assoc($contact, array_intersect_key($linked, $contact));
            if ($changes) {
                Database::update('customers', $changes, 'id = ?', [$linked['id']]);
                AuditLogService::log('UPDATE_CUSTOMER', 'customers', (int) $linked['id'], array_intersect_key($linked, $changes), $changes + ['from_user' => $userId], $companyId);
            }
            return (int) $linked['id'];
        }

        $match = ($u['phone'] || $u['email']) ? Database::fetch(
            'SELECT * FROM customers WHERE company_id = ? AND user_id IS NULL AND deleted_at IS NULL AND ((phone IS NOT NULL AND phone = ?) OR (email IS NOT NULL AND email = ?)) ORDER BY id LIMIT 1',
            [$companyId, $u['phone'], $u['email']]
        ) : null;
        if ($match) {
            Database::update('customers', ['user_id' => $userId], 'id = ?', [$match['id']]);
            AuditLogService::log('LINK_CUSTOMER_USER', 'customers', (int) $match['id'], ['user_id' => null], ['user_id' => $userId], $companyId);
            return (int) $match['id'];
        }

        $data = $contact + [
            'company_id' => $companyId, 'customer_code' => NumberGeneratorService::next('customer', $companyId),
            'status' => 'active', 'user_id' => $userId, 'created_by' => AuthService::id(),
        ];
        $id = Database::insert('customers', $data);
        AuditLogService::log('CREATE_CUSTOMER', 'customers', $id, null, $data + ['from_user' => $userId], $companyId);
        return $id;
    }

    /** Customer record linked to a user account, if any. */
    public static function forUser(int $userId): ?array
    {
        return Database::fetch('SELECT id, customer_code, name FROM customers WHERE company_id = ? AND user_id = ? AND deleted_at IS NULL', [Tenant::id(), $userId]);
    }

    public static function delete(int $id): void
    {
        $c = self::find($id);
        if (Database::value("SELECT 1 FROM share_sales WHERE customer_id = ? AND status <> 'cancelled' LIMIT 1", [$id])
            || Database::value("SELECT 1 FROM invoices WHERE customer_id = ? AND status <> 'cancelled' AND deleted_at IS NULL LIMIT 1", [$id])) {
            throw ValidationException::rule('This customer has sales or invoices and cannot be deleted. Set the status to inactive instead.');
        }
        Database::run('UPDATE customers SET deleted_at = NOW() WHERE id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('DELETE_CUSTOMER', 'customers', $id, $c, null);
    }

    public static function lookup(string $term): array
    {
        return Query::table('customers c')->select("c.id, CONCAT(c.name, ' — ', COALESCE(c.phone, c.email, ''), ' (', c.customer_code, ')') AS text")
            ->where('c.company_id = ?', Tenant::id())->where('c.deleted_at IS NULL')->where("c.status = 'active'")
            ->search(['c.name', 'c.phone', 'c.email', 'c.customer_code', 'c.nid'], $term)->orderBy('c.name')->get(20);
    }

    /** Options for small selects: preloaded only when few customers, otherwise use lookup. */
    public static function options(?int $selectedId = null): array
    {
        $count = (int) Database::value('SELECT COUNT(*) FROM customers WHERE company_id = ? AND deleted_at IS NULL', [Tenant::id()]);
        if ($count <= 300) {
            return Database::pairs("SELECT id, CONCAT(name, ' (', customer_code, ')') FROM customers WHERE company_id = ? AND deleted_at IS NULL AND status = 'active' ORDER BY name", [Tenant::id()]);
        }
        return $selectedId ? Database::pairs("SELECT id, CONCAT(name, ' (', customer_code, ')') FROM customers WHERE id = ? AND company_id = ?", [$selectedId, Tenant::id()]) : [];
    }

    /** Financial summary for the profile page. */
    public static function summary(int $id): array
    {
        $r = Database::fetch(
            "SELECT COALESCE(SUM(grand_total), 0) AS invoiced, COALESCE(SUM(paid_amount), 0) AS paid, COALESCE(SUM(due_amount), 0) AS due
             FROM invoices WHERE customer_id = ? AND company_id = ? AND status NOT IN ('cancelled', 'draft') AND deleted_at IS NULL",
            [$id, Tenant::id()]
        );
        $r['shares'] = (int) Database::value("SELECT COALESCE(SUM(shares), 0) FROM share_sales WHERE customer_id = ? AND company_id = ? AND status = 'confirmed'", [$id, Tenant::id()]);
        return $r;
    }
}
