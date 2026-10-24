<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Query;
use App\Core\ValidationException;
use App\Core\Validator;

/** CRM timeline entries for leads and customers. */
final class ActivityService
{
    /** Log an activity entered by a user. */
    public static function create(string $subject, int $subjectId, array $input): int
    {
        self::assertSubject($subject, $subjectId);
        $data = Validator::make($input, [
            'type_id'        => ['required', MasterDataService::rule('activity_type', true)],
            'description'    => 'required|max:5000',
            'activity_date'  => 'nullable|date',
            'follow_up_date' => 'nullable|date',
        ], ['type_id' => 'Activity type']);

        $id = Database::insert('activities', [
            'company_id' => Tenant::id(),
            $subject . '_id' => $subjectId,
            'type_id' => $data['type_id'],
            'description' => $data['description'],
            'activity_at' => ($data['activity_date'] ?? today()) . ' ' . date('H:i:s'),
            'follow_up_date' => $data['follow_up_date'],
            'created_by' => AuthService::id(),
        ]);
        // Keep the lead's next follow-up in sync.
        if ($subject === 'lead' && $data['follow_up_date']) {
            Database::update('leads', ['follow_up_date' => $data['follow_up_date']], 'id = ? AND company_id = ?', [$subjectId, Tenant::id()]);
        }
        return $id;
    }

    /** System-generated entry (offer sent, payment, status change ...). Never throws. */
    public static function system(string $subject, int $subjectId, string $typeCode, string $description, ?int $documentId = null): void
    {
        try {
            $typeId = MasterDataService::idByCode('activity_type', $typeCode) ?? MasterDataService::idByCode('activity_type', 'note');
            if (!$typeId) {
                return;
            }
            Database::insert('activities', [
                'company_id' => Tenant::id(), $subject . '_id' => $subjectId, 'type_id' => $typeId,
                'description' => mb_substr($description, 0, 5000), 'activity_at' => now(), 'document_id' => $documentId,
                'created_by' => AuthService::id(),
            ]);
        } catch (\Throwable $e) {
            \App\Core\Logger::error('Activity log failed: ' . $e->getMessage());
        }
    }

    public static function timeline(string $subject, int $subjectId, int $limit = 100): array
    {
        $col = $subject === 'lead' ? 'a.lead_id' : 'a.customer_id';
        return Database::fetchAll(
            "SELECT a.*, t.name AS type_name, t.color AS type_color, t.code AS type_code, u.name AS user_name, d.title AS document_title
             FROM activities a JOIN master_data t ON t.id = a.type_id
             LEFT JOIN users u ON u.id = a.created_by LEFT JOIN documents d ON d.id = a.document_id AND d.deleted_at IS NULL
             WHERE $col = ? AND a.company_id = ? ORDER BY a.activity_at DESC, a.id DESC LIMIT $limit",
            [$subjectId, Tenant::id()]
        );
    }

    public static function query(array $f): Query
    {
        $q = Query::table('activities a')
            ->select('a.*, t.name AS type_name, t.color AS type_color, u.name AS user_name, l.name AS lead_name, l.lead_code, c.name AS customer_name, c.customer_code')
            ->join('JOIN master_data t ON t.id = a.type_id')
            ->join('LEFT JOIN users u ON u.id = a.created_by')
            ->join('LEFT JOIN leads l ON l.id = a.lead_id')
            ->join('LEFT JOIN customers c ON c.id = a.customer_id')
            ->where('a.company_id = ?', Tenant::id())
            ->search(['a.description', 'l.name', 'c.name'], $f['q'] ?? '')
            ->whereIf($f['type'] ?? '', 'a.type_id = ?', (int) ($f['type'] ?? 0))
            ->whereIf($f['user'] ?? '', 'a.created_by = ?', (int) ($f['user'] ?? 0))
            ->dateRange('DATE(a.activity_at)', $f['from'] ?? null, $f['to'] ?? null)
            ->orderBy('a.activity_at DESC, a.id DESC');
        if (!AuthService::can('leads.view_all')) {
            $uid = (int) AuthService::id();
            $q->where('(a.created_by = ? OR l.assigned_to = ?)', $uid, $uid);
        }
        return $q;
    }

    public static function delete(int $id): void
    {
        $row = Database::fetch('SELECT * FROM activities WHERE id = ? AND company_id = ?', [$id, Tenant::id()]);
        if (!$row) {
            throw new \App\Core\HttpException(404);
        }
        Database::run('DELETE FROM activities WHERE id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('DELETE_ACTIVITY', 'activities', $id, $row, null);
    }

    /** Ensure the lead/customer exists in this company and is visible to the user. */
    public static function assertSubject(string $subject, int $id): void
    {
        match ($subject) {
            'lead' => LeadService::find($id),
            'customer' => CustomerService::find($id),
            default => throw new ValidationException(['_' => 'Invalid subject']),
        };
    }
}
