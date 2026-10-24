<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Query;
use App\Core\ValidationException;
use App\Core\Validator;

/** Private documents stored under storage/private, downloadable only through access checks. */
final class DocumentService
{
    public static function query(array $f): Query
    {
        $q = Query::table('documents d')
            ->select('d.*, t.name AS type_name, p.project_name, c.name AS customer_name, l.name AS lead_name, u.name AS uploader')
            ->join('LEFT JOIN master_data t ON t.id = d.document_type_id')
            ->join('LEFT JOIN projects p ON p.id = d.project_id')
            ->join('LEFT JOIN customers c ON c.id = d.customer_id')
            ->join('LEFT JOIN leads l ON l.id = d.lead_id')
            ->join('LEFT JOIN users u ON u.id = d.uploaded_by')
            ->where('d.company_id = ?', Tenant::id())
            ->where('d.deleted_at IS NULL')
            ->search(['d.title', 'd.original_name', 'c.name', 'l.name'], $f['q'] ?? '')
            ->whereIf($f['type'] ?? '', 'd.document_type_id = ?', (int) ($f['type'] ?? 0))
            ->whereIf($f['project'] ?? '', 'd.project_id = ?', (int) ($f['project'] ?? 0))
            ->whereIf($f['customer'] ?? '', 'd.customer_id = ?', (int) ($f['customer'] ?? 0))
            ->whereIf($f['lead'] ?? '', 'd.lead_id = ?', (int) ($f['lead'] ?? 0))
            ->orderBy('d.id DESC');
        if (!AuthService::can('leads.view_all')) {
            // Lead documents follow lead visibility.
            $uid = (int) AuthService::id();
            $q->where('(d.lead_id IS NULL OR l.assigned_to = ? OR l.created_by = ?)', $uid, $uid);
        }
        return $q;
    }

    public static function forSubject(string $col, int $id): array
    {
        if (!in_array($col, ['customer_id', 'lead_id', 'project_id'], true)) {
            throw new \InvalidArgumentException();
        }
        return Database::fetchAll(
            "SELECT d.*, t.name AS type_name FROM documents d LEFT JOIN master_data t ON t.id = d.document_type_id
             WHERE d.$col = ? AND d.company_id = ? AND d.deleted_at IS NULL ORDER BY d.id DESC",
            [$id, Tenant::id()]
        );
    }

    /** Find with company ownership + linked-record visibility check (IDOR protection). */
    public static function find(int $id): array
    {
        $doc = Tenant::findOrFail('documents', $id);
        if ($doc['lead_id'] && !$doc['customer_id']) {
            LeadService::find((int) $doc['lead_id']); // throws 404 when the lead isn't visible
        }
        return $doc;
    }

    public static function upload(array $input, ?array $file): int
    {
        if (!$file) {
            throw new ValidationException(['file' => 'Please choose a file.']);
        }
        $data = Validator::make($input, [
            'title'            => 'nullable|max:200',
            'document_type_id' => ['nullable', MasterDataService::rule('document_type')],
            'project_id'       => ['nullable', ProjectService::rule()],
            'customer_id'      => ['nullable', CustomerService::rule()],
            'lead_id'          => ['nullable', static fn($v) => Tenant::owns('leads', $v, 'deleted_at IS NULL') ? null : 'Invalid lead.'],
            'is_shareable'     => 'bool',
        ], ['document_type_id' => 'Document type']);
        if ($data['lead_id']) {
            LeadService::find((int) $data['lead_id']);
        }
        $stored = FileUploadService::store($file, ['document', 'image'], 'file');
        $row = $data + [
            'company_id' => Tenant::id(),
            'title' => $data['title'] ?: pathinfo($stored['original_name'], PATHINFO_FILENAME),
            'original_name' => $stored['original_name'], 'stored_name' => $stored['stored_name'],
            'mime' => $stored['mime'], 'size' => $stored['size'], 'uploaded_by' => AuthService::id(),
        ];
        $id = Database::insert('documents', $row);
        AuditLogService::log('UPLOAD_DOCUMENT', 'documents', $id, null, array_diff_key($row, ['stored_name' => 1]));
        return $id;
    }

    public static function delete(int $id): array
    {
        $doc = self::find($id);
        Database::run('UPDATE documents SET deleted_at = NOW() WHERE id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('DELETE_DOCUMENT', 'documents', $id, ['title' => $doc['title'], 'original_name' => $doc['original_name']], null);
        return $doc;
    }

    public static function toggleShareable(int $id): array
    {
        $doc = self::find($id);
        $new = $doc['is_shareable'] ? 0 : 1;
        Database::update('documents', ['is_shareable' => $new], 'id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('UPDATE_DOCUMENT', 'documents', $id, ['is_shareable' => $doc['is_shareable']], ['is_shareable' => $new]);
        return $doc + ['is_shareable_new' => $new];
    }

    public static function icon(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'file-earmark-image',
            $mime === 'application/pdf' => 'file-earmark-pdf',
            str_contains($mime, 'word') => 'file-earmark-word',
            str_contains($mime, 'excel') || str_contains($mime, 'sheet') => 'file-earmark-excel',
            default => 'file-earmark',
        };
    }

    public static function humanSize(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : max(1, (int) round($bytes / 1024)) . ' KB';
    }
}
