<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Query;
use App\Core\Request;
use App\Core\ValidationException;
use App\Core\Validator;

final class EmployeeService
{
    public static function query(array $f): Query
    {
        return Query::table('employees e')
            ->select('e.*, d.name AS department, g.name AS designation')
            ->join('LEFT JOIN master_data d ON d.id = e.department_id')
            ->join('LEFT JOIN master_data g ON g.id = e.designation_id')
            ->where('e.company_id = ?', Tenant::id())
            ->where('e.deleted_at IS NULL')
            ->search(['e.name', 'e.phone', 'e.email', 'e.employee_code'], $f['q'] ?? '')
            ->whereIf($f['status'] ?? '', 'e.status = ?')
            ->whereIf($f['department'] ?? '', 'e.department_id = ?', (int) ($f['department'] ?? 0))
            ->whereIf($f['designation'] ?? '', 'e.designation_id = ?', (int) ($f['designation'] ?? 0))
            ->sort($f['sort'] ?? null, $f['dir'] ?? null, ['name' => 'e.name', 'code' => 'e.id', 'joining' => 'e.joining_date', 'salary' => 'e.salary'], 'e.name ASC');
    }

    public static function find(int $id): array
    {
        $e = Tenant::findOrFail('employees', $id);
        $names = MasterDataService::options('department', false) + MasterDataService::options('designation', false);
        $e['department'] = $names[$e['department_id']] ?? null;
        $e['designation'] = $names[$e['designation_id']] ?? null;
        return $e;
    }

    public static function save(array $input, ?int $id = null): int
    {
        $old = $id ? self::find($id) : null;
        $data = Validator::make($input, [
            'name'           => 'required|max:150',
            'phone'          => 'nullable|phone',
            'email'          => 'nullable|email',
            'address'        => 'nullable|max:1000',
            'department_id'  => ['nullable', MasterDataService::rule('department')],
            'designation_id' => ['nullable', MasterDataService::rule('designation')],
            'joining_date'   => 'nullable|date',
            'salary'         => 'required|money',
            'status'         => 'required|in:active,inactive,resigned',
            'user_id'        => ['nullable', static fn($v) => Tenant::owns('users', $v, 'deleted_at IS NULL') ? null : 'Invalid user.'],
            'notes'          => 'nullable|max:3000',
        ], ['department_id' => 'Department', 'designation_id' => 'Designation', 'salary' => 'Basic salary', 'user_id' => 'Linked user']);
        if ($file = Request::file('photo')) {
            $data['photo'] = FileUploadService::store($file, ['image'], 'photo', 3)['stored_name'];
        }
        if ($data['user_id'] && Database::value('SELECT 1 FROM employees WHERE user_id = ? AND id <> ? AND deleted_at IS NULL', [$data['user_id'], $id ?? 0])) {
            throw new ValidationException(['user_id' => 'This user is already linked to another employee.']);
        }

        return Database::transaction(static function () use ($data, $id, $old, $input) {
            if ($id) {
                Database::update('employees', $data, 'id = ? AND company_id = ?', [$id, Tenant::id()]);
                if (isset($data['photo']) && $old['photo']) {
                    FileUploadService::delete($old['photo']);
                }
                AuditLogService::log('UPDATE_EMPLOYEE', 'employees', $id, $old, $data);
            } else {
                $data += ['company_id' => Tenant::id(), 'employee_code' => NumberGeneratorService::next('employee'), 'created_by' => AuthService::id()];
                $id = Database::insert('employees', $data);
                AuditLogService::log('CREATE_EMPLOYEE', 'employees', $id, null, $data);
            }
            if (isset($input['component']) && is_array($input['component']) && AuthService::can('salary.manage')) {
                SalaryService::saveStructure($id, $input['component']);
            }
            return $id;
        });
    }

    public static function delete(int $id): void
    {
        $e = self::find($id);
        if (Database::value("SELECT 1 FROM salary_payments WHERE employee_id = ? AND status = 'paid' LIMIT 1", [$id])) {
            throw ValidationException::rule('This employee has salary payments. Set status to resigned/inactive instead.');
        }
        Database::run('UPDATE employees SET deleted_at = NOW() WHERE id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('DELETE_EMPLOYEE', 'employees', $id, $e, null);
    }

    public static function options(bool $activeOnly = true): array
    {
        return Database::pairs(
            "SELECT id, CONCAT(name, ' (', employee_code, ')') FROM employees WHERE company_id = ? AND deleted_at IS NULL" . ($activeOnly ? " AND status = 'active'" : '') . ' ORDER BY name',
            [Tenant::id()]
        );
    }
}
