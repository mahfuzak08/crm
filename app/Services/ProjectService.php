<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Query;
use App\Core\ValidationException;
use App\Core\Validator;

final class ProjectService
{
    public static function list(array $f, int $page): array
    {
        return self::query($f)->paginate($page);
    }

    public static function query(array $f): Query
    {
        return Query::table('projects p')
            ->select("p.*, s.name AS status_name, s.color AS status_color,
                COALESCE((SELECT SUM(ss.shares) FROM share_sales ss WHERE ss.project_id = p.id AND ss.status = 'confirmed'), 0) AS sold_shares,
                COALESCE((SELECT SUM(ss.shares) FROM share_sales ss WHERE ss.project_id = p.id AND ss.status = 'reserved'), 0) AS reserved_shares")
            ->join('LEFT JOIN master_data s ON s.id = p.status_id')
            ->where('p.company_id = ?', Tenant::id())
            ->where('p.deleted_at IS NULL')
            ->search(['p.project_name', 'p.project_code', 'p.location'], $f['q'] ?? '')
            ->whereIf($f['status'] ?? '', 'p.status_id = ?', (int) ($f['status'] ?? 0))
            ->sort($f['sort'] ?? null, $f['dir'] ?? null, ['name' => 'p.project_name', 'code' => 'p.project_code', 'created' => 'p.created_at'], 'p.project_name ASC');
    }

    public static function find(int $id): array
    {
        $p = Tenant::findOrFail('projects', $id);
        $p['status'] = $p['status_id'] ? Database::fetch('SELECT name, color FROM master_data WHERE id = ?', [$p['status_id']]) : null;
        return $p;
    }

    /** [id => "CODE — Name"] for dropdowns. */
    public static function options(): array
    {
        static $c = null;
        return $c ??= Database::pairs(
            "SELECT id, CONCAT(project_name, ' (', project_code, ')') FROM projects WHERE company_id = ? AND deleted_at IS NULL ORDER BY project_name",
            [Tenant::id()]
        );
    }

    public static function rule(): \Closure
    {
        return static fn($v) => Tenant::owns('projects', $v, 'deleted_at IS NULL') ? null : 'Please select a valid project.';
    }

    public static function save(array $input, ?int $id = null): int
    {
        $old = $id ? self::find($id) : null;
        $data = Validator::make($input, [
            'project_name'    => 'required|max:150',
            'project_code'    => ['nullable', 'code', static fn($v) => Database::value('SELECT 1 FROM projects WHERE company_id = ? AND project_code = ? AND id <> ?', [Tenant::id(), $v, $id ?? 0]) ? 'Code already used.' : null],
            'location'        => 'nullable|max:255',
            'description'     => 'nullable|max:5000',
            'total_land_area' => 'nullable|decimal',
            'land_unit'       => 'nullable|max:30',
            'total_shares'    => 'required|integer|gte:0|lte:100000000',
            'share_unit'      => 'nullable|max:150',
            'share_price'     => 'required|money',
            'registration_fee'=> 'nullable|money',
            'development_fee' => 'nullable|money',
            'status_id'       => ['nullable', MasterDataService::rule('project_status')],
            'start_date'      => 'nullable|date',
            'expected_completion_date' => 'nullable|date|after_or_equal:start_date',
        ], ['project_name' => 'Project name', 'status_id' => 'Status', 'expected_completion_date' => 'Expected completion']);
        $data['registration_fee'] ??= '0.00';
        $data['development_fee'] ??= '0.00';

        return Database::transaction(static function () use ($data, $id, $old) {
            if ($id) {
                // Lock project and make sure total shares never drops below what is committed.
                $committed = ShareService::committed($id, true);
                if ((int) $data['total_shares'] < $committed) {
                    throw new ValidationException(['total_shares' => "Total shares cannot be less than sold + reserved ($committed)."]);
                }
                $data['project_code'] ??= $old['project_code'];
                Database::update('projects', $data, 'id = ? AND company_id = ?', [$id, Tenant::id()]);
                AuditLogService::log('UPDATE_PROJECT', 'projects', $id, $old, $data);
                return $id;
            }
            $data['project_code'] ??= NumberGeneratorService::next('project');
            $data['company_id'] = Tenant::id();
            $data['created_by'] = AuthService::id();
            $newId = Database::insert('projects', $data);
            AuditLogService::log('CREATE_PROJECT', 'projects', $newId, null, $data);
            return $newId;
        });
    }

    public static function delete(int $id): void
    {
        $p = self::find($id);
        if (Database::value("SELECT 1 FROM share_sales WHERE project_id = ? AND status <> 'cancelled' LIMIT 1", [$id])) {
            throw ValidationException::rule('This project has share sales and cannot be deleted.');
        }
        Database::run('UPDATE projects SET deleted_at = NOW() WHERE id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('DELETE_PROJECT', 'projects', $id, $p, null);
    }
}
