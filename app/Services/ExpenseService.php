<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Money;
use App\Core\Query;
use App\Core\Request;
use App\Core\ValidationException;
use App\Core\Validator;

/**
 * Expenses. Status workflow is code-controlled (pending → approved, or cancelled)
 * because totals depend on it; categories & payment methods are dynamic master data.
 */
final class ExpenseService
{
    public const STATUSES = ['pending', 'approved', 'cancelled'];

    public static function query(array $f): Query
    {
        return Query::table('expenses x')
            ->select('x.*, c.name AS category_name, m.name AS method_name, p.project_name, u.name AS created_by_name')
            ->join('JOIN master_data c ON c.id = x.category_id')
            ->join('LEFT JOIN master_data m ON m.id = x.payment_method_id')
            ->join('LEFT JOIN projects p ON p.id = x.project_id')
            ->join('LEFT JOIN users u ON u.id = x.created_by')
            ->where('x.company_id = ?', Tenant::id())
            ->where('x.deleted_at IS NULL')
            ->search(['x.expense_number', 'x.description', 'x.reference'], $f['q'] ?? '')
            ->whereIf($f['category'] ?? '', 'x.category_id = ?', (int) ($f['category'] ?? 0))
            ->whereIf($f['status'] ?? '', 'x.status = ?')
            ->whereIf($f['project'] ?? '', 'x.project_id = ?', (int) ($f['project'] ?? 0))
            ->dateRange('x.expense_date', $f['from'] ?? null, $f['to'] ?? null)
            ->sort($f['sort'] ?? null, $f['dir'] ?? null, ['number' => 'x.id', 'date' => 'x.expense_date', 'amount' => 'x.amount'], 'x.expense_date DESC, x.id DESC');
    }

    public static function find(int $id): array
    {
        $x = Database::fetch(
            'SELECT x.*, c.name AS category_name, m.name AS method_name, p.project_name, u.name AS created_by_name, a.name AS approved_by_name
             FROM expenses x JOIN master_data c ON c.id = x.category_id LEFT JOIN master_data m ON m.id = x.payment_method_id
             LEFT JOIN projects p ON p.id = x.project_id LEFT JOIN users u ON u.id = x.created_by LEFT JOIN users a ON a.id = x.approved_by
             WHERE x.id = ? AND x.company_id = ? AND x.deleted_at IS NULL',
            [$id, Tenant::id()]
        );
        if (!$x) {
            throw new HttpException(404);
        }
        return $x;
    }

    public static function save(array $input, ?int $id = null): int
    {
        $old = $id ? self::find($id) : null;
        if ($old && $old['status'] === 'cancelled') {
            throw ValidationException::rule('Cancelled expenses cannot be edited.');
        }
        $data = Validator::make($input, [
            'category_id'       => ['required', MasterDataService::rule('expense_category')],
            'project_id'        => ['nullable', ProjectService::rule()],
            'expense_date'      => 'required|date',
            'amount'            => 'required|money',
            'payment_method_id' => ['nullable', MasterDataService::rule('payment_method')],
            'reference'         => 'nullable|max:150',
            'description'       => 'nullable|max:3000',
        ], ['category_id' => 'Category', 'payment_method_id' => 'Payment method']);
        if (Money::toCents($data['amount']) <= 0) {
            throw new ValidationException(['amount' => 'Amount must be greater than zero.']);
        }
        if ($file = Request::file('attachment')) {
            $st = FileUploadService::store($file, ['document', 'image'], 'attachment');
            $data += ['attachment' => $st['stored_name'], 'attachment_name' => $st['original_name'], 'attachment_mime' => $st['mime']];
        }

        if ($id) {
            if ($old['status'] === 'approved' && !AuthService::can('expenses.approve')) {
                $data['status'] = 'pending';      // financial change needs re-approval
                $data['approved_by'] = null;
            }
            Database::update('expenses', $data, 'id = ? AND company_id = ?', [$id, Tenant::id()]);
            if (isset($data['attachment']) && $old['attachment']) {
                FileUploadService::delete($old['attachment']);
            }
            AuditLogService::log('UPDATE_EXPENSE', 'expenses', $id, $old, $data);
            return $id;
        }
        return Database::transaction(static function () use ($data) {
            $needsApproval = SettingsService::get('expense.require_approval') === '1' && !AuthService::can('expenses.approve');
            $data += [
                'company_id' => Tenant::id(), 'expense_number' => NumberGeneratorService::next('expense'),
                'status' => $needsApproval ? 'pending' : 'approved', 'created_by' => AuthService::id(),
                'approved_by' => $needsApproval ? null : AuthService::id(),
            ];
            $id = Database::insert('expenses', $data);
            AuditLogService::log('CREATE_EXPENSE', 'expenses', $id, null, $data);
            return $id;
        });
    }

    public static function approve(int $id): void
    {
        $x = self::find($id);
        if ($x['status'] !== 'pending') {
            throw ValidationException::rule('Only pending expenses can be approved.');
        }
        Database::update('expenses', ['status' => 'approved', 'approved_by' => AuthService::id()], 'id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('APPROVE_EXPENSE', 'expenses', $id, ['status' => 'pending'], ['status' => 'approved']);
    }

    public static function cancel(int $id, ?string $reason): void
    {
        $x = self::find($id);
        if ($x['status'] === 'cancelled') {
            throw ValidationException::rule('Already cancelled.');
        }
        Database::update('expenses', ['status' => 'cancelled', 'description' => trim(($x['description'] ?? '') . "\n[Cancelled] " . ($reason ?? ''))], 'id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('CANCEL_EXPENSE', 'expenses', $id, ['status' => $x['status']], ['status' => 'cancelled', 'reason' => $reason]);
    }
}
