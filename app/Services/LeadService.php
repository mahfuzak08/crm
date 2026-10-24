<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Query;
use App\Core\ValidationException;
use App\Core\Validator;

final class LeadService
{
    /** Users without leads.view_all only see leads assigned to / created by them. */
    private static function scope(Query $q, string $alias = 'l'): Query
    {
        if (!AuthService::can('leads.view_all')) {
            $uid = (int) AuthService::id();
            $q->where("($alias.assigned_to = ? OR $alias.created_by = ?)", $uid, $uid);
        }
        return $q;
    }

    public static function query(array $f): Query
    {
        $q = Query::table('leads l')
            ->select('l.*, s.name AS status_name, s.color AS status_color, s.code AS status_code, src.name AS source_name, u.name AS assigned_name, p.project_name')
            ->join('JOIN master_data s ON s.id = l.status_id')
            ->join('LEFT JOIN master_data src ON src.id = l.source_id')
            ->join('LEFT JOIN users u ON u.id = l.assigned_to')
            ->join('LEFT JOIN projects p ON p.id = l.project_id')
            ->where('l.company_id = ?', Tenant::id())
            ->where('l.deleted_at IS NULL')
            ->search(['l.name', 'l.phone', 'l.email', 'l.lead_code'], $f['q'] ?? '')
            ->whereIf($f['status'] ?? '', 'l.status_id = ?', (int) ($f['status'] ?? 0))
            ->whereIf($f['source'] ?? '', 'l.source_id = ?', (int) ($f['source'] ?? 0))
            ->whereIf($f['project'] ?? '', 'l.project_id = ?', (int) ($f['project'] ?? 0))
            ->dateRange('DATE(l.created_at)', $f['from'] ?? null, $f['to'] ?? null);
        if (($f['assigned'] ?? '') === 'me') {
            $q->where('l.assigned_to = ?', (int) AuthService::id());
        } elseif (($f['assigned'] ?? '') === 'none') {
            $q->where('l.assigned_to IS NULL');
        } elseif (ctype_digit((string) ($f['assigned'] ?? ''))) {
            $q->where('l.assigned_to = ?', (int) $f['assigned']);
        }
        match ($f['followup'] ?? '') {
            'today'    => $q->where('l.follow_up_date = CURDATE()'),
            'overdue'  => $q->where("l.follow_up_date < CURDATE() AND s.code NOT IN ('converted', 'lost')"),
            'upcoming' => $q->where('l.follow_up_date > CURDATE()'),
            default    => null,
        };
        return self::scope($q)->sort($f['sort'] ?? null, $f['dir'] ?? null, [
            'name' => 'l.name', 'code' => 'l.id', 'followup' => 'l.follow_up_date', 'created' => 'l.created_at', 'status' => 's.sort_order',
        ], 'l.id DESC');
    }

    public static function find(int $id): array
    {
        $q = Query::table('leads l')
            ->select('l.*, s.name AS status_name, s.color AS status_color, s.code AS status_code, src.name AS source_name, u.name AS assigned_name, p.project_name, cb.name AS created_by_name')
            ->join('JOIN master_data s ON s.id = l.status_id')
            ->join('LEFT JOIN master_data src ON src.id = l.source_id')
            ->join('LEFT JOIN users u ON u.id = l.assigned_to')
            ->join('LEFT JOIN users cb ON cb.id = l.created_by')
            ->join('LEFT JOIN projects p ON p.id = l.project_id')
            ->where('l.id = ?', $id)->where('l.company_id = ?', Tenant::id())->where('l.deleted_at IS NULL');
        $lead = self::scope($q)->first();
        if (!$lead) {
            throw new HttpException(404);
        }
        return $lead;
    }

    public static function save(array $input, ?int $id = null): int
    {
        $old = $id ? self::find($id) : null;
        $data = Validator::make($input, [
            'name'           => 'required|max:150',
            'phone'          => 'nullable|phone',
            'email'          => 'nullable|email',
            'address'        => 'nullable|max:1000',
            'project_id'     => ['nullable', ProjectService::rule()],
            'source_id'      => ['nullable', MasterDataService::rule('lead_source')],
            'status_id'      => ['nullable', MasterDataService::rule('lead_status')],
            'assigned_to'    => ['nullable', static fn($v) => Tenant::owns('users', $v, "deleted_at IS NULL AND status = 'active'") ? null : 'Invalid user.'],
            'follow_up_date' => 'nullable|date',
            'notes'          => 'nullable|max:5000',
        ], ['project_id' => 'Project', 'source_id' => 'Source', 'status_id' => 'Status', 'assigned_to' => 'Assigned to']);
        if (!$data['phone'] && !$data['email']) {
            throw new ValidationException(['phone' => 'Provide a phone number or an email.']);
        }
        $data['status_id'] ??= $old['status_id'] ?? MasterDataService::idByCode('lead_status', 'new');

        // Only users with leads.assign may assign to someone else.
        if (!AuthService::can('leads.assign')) {
            $data['assigned_to'] = $old ? $old['assigned_to'] : AuthService::id();
        }

        // Duplicate warning by phone (same company).
        if ($data['phone'] && empty($input['allow_duplicate'])) {
            $dup = Database::fetch('SELECT id, lead_code, name FROM leads WHERE company_id = ? AND phone = ? AND deleted_at IS NULL AND id <> ?', [Tenant::id(), $data['phone'], $id ?? 0]);
            if ($dup) {
                throw new ValidationException(['phone' => "A lead with this phone already exists ({$dup['lead_code']} — {$dup['name']}). Tick \"allow duplicate\" to save anyway."]);
            }
        }

        return Database::transaction(static function () use ($data, $id, $old) {
            if ($id) {
                Database::update('leads', $data, 'id = ? AND company_id = ?', [$id, Tenant::id()]);
                AuditLogService::log('UPDATE_LEAD', 'leads', $id, $old, $data);
                if ((int) $old['status_id'] !== (int) $data['status_id']) {
                    ActivityService::system('lead', $id, 'note', 'Status changed to ' . (MasterDataService::options('lead_status', false)[$data['status_id']] ?? ''));
                }
                if ($data['assigned_to'] && (int) $data['assigned_to'] !== (int) $old['assigned_to'] && (int) $data['assigned_to'] !== AuthService::id()) {
                    NotificationService::notifyUser((int) $data['assigned_to'], 'Lead assigned to you: ' . $data['name'], null, '/leads/' . $id);
                }
                return $id;
            }
            $data += ['company_id' => Tenant::id(), 'lead_code' => NumberGeneratorService::next('lead'), 'created_by' => AuthService::id()];
            $newId = Database::insert('leads', $data);
            AuditLogService::log('CREATE_LEAD', 'leads', $newId, null, $data);
            if ($data['assigned_to'] && (int) $data['assigned_to'] !== AuthService::id()) {
                NotificationService::notifyUser((int) $data['assigned_to'], 'New lead assigned: ' . $data['name'], null, '/leads/' . $newId);
            }
            return $newId;
        });
    }

    public static function setStatus(int $id, int $statusId): void
    {
        $lead = self::find($id);
        if (!Tenant::owns('master_data', $statusId, "type = 'lead_status' AND is_active = 1")) {
            throw ValidationException::rule('Invalid status.');
        }
        Database::update('leads', ['status_id' => $statusId], 'id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('UPDATE_LEAD_STATUS', 'leads', $id, ['status_id' => $lead['status_id']], ['status_id' => $statusId]);
        ActivityService::system('lead', $id, 'note', 'Status changed to ' . (MasterDataService::options('lead_status')[$statusId] ?? ''));
    }

    public static function assign(int $id, ?int $userId): void
    {
        $lead = self::find($id);
        if ($userId && !Tenant::owns('users', $userId, "deleted_at IS NULL AND status = 'active'")) {
            throw ValidationException::rule('Invalid user.');
        }
        Database::update('leads', ['assigned_to' => $userId], 'id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('ASSIGN_LEAD', 'leads', $id, ['assigned_to' => $lead['assigned_to']], ['assigned_to' => $userId]);
        if ($userId && $userId !== AuthService::id()) {
            NotificationService::notifyUser($userId, 'Lead assigned to you: ' . $lead['name'], null, '/leads/' . $id);
        }
    }

    /** Convert lead to customer (idempotent: returns existing customer if already converted). */
    public static function convert(int $id): int
    {
        return Database::transaction(static function () use ($id) {
            $lead = self::find($id);
            Database::fetch('SELECT id FROM leads WHERE id = ? FOR UPDATE', [$id]);
            if ($lead['customer_id']) {
                return (int) $lead['customer_id'];
            }
            $customerId = CustomerService::createFromLead($lead);
            Database::update('leads', [
                'customer_id' => $customerId, 'converted_at' => now(),
                'status_id' => MasterDataService::idByCode('lead_status', 'converted') ?? $lead['status_id'],
            ], 'id = ? AND company_id = ?', [$id, Tenant::id()]);
            // Carry lead documents over to the customer.
            Database::run('UPDATE documents SET customer_id = ? WHERE lead_id = ? AND company_id = ? AND customer_id IS NULL', [$customerId, $id, Tenant::id()]);
            ActivityService::system('lead', $id, 'note', 'Converted to customer');
            ActivityService::system('customer', $customerId, 'note', 'Created from lead ' . $lead['lead_code']);
            AuditLogService::log('CONVERT_LEAD', 'leads', $id, null, ['customer_id' => $customerId]);
            return $customerId;
        });
    }

    public static function delete(int $id): void
    {
        $lead = self::find($id);
        Database::run('UPDATE leads SET deleted_at = NOW() WHERE id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('DELETE_LEAD', 'leads', $id, $lead, null);
    }

    /** Open (unconverted) leads for dropdowns: [id => label], respects visibility. */
    public static function options(): array
    {
        $q = Query::table('leads l')->select("l.id, CONCAT(l.name, ' (', l.lead_code, ')') AS label")
            ->where('l.company_id = ?', Tenant::id())->where('l.deleted_at IS NULL')->where('l.customer_id IS NULL')->orderBy('l.id DESC');
        return array_column(self::scope($q)->get(300), 'label', 'id');
    }

    /** Lookup for selects (respects visibility). */
    public static function lookup(string $term): array
    {
        $q = Query::table('leads l')->select("l.id, CONCAT(l.name, ' — ', COALESCE(l.phone, l.email, ''), ' (', l.lead_code, ')') AS text")
            ->where('l.company_id = ?', Tenant::id())->where('l.deleted_at IS NULL')
            ->search(['l.name', 'l.phone', 'l.email', 'l.lead_code'], $term)->orderBy('l.name');
        return self::scope($q)->get(20);
    }
}
