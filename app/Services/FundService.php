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
 * Fund contributions: money put into the company/project by directors (users) or outside
 * contributors for development or running costs. Not income — excluded from profit/loss.
 */
final class FundService
{
    public const TYPES = ['capital' => 'Capital / Investment', 'loan' => 'Loan (refundable)', 'other' => 'Other'];
    public const STATUSES = ['received', 'cancelled'];

    public static function query(array $f): Query
    {
        return Query::table('fund_contributions x')
            ->select('x.*, m.name AS method_name, p.project_name')
            ->join('LEFT JOIN master_data m ON m.id = x.payment_method_id')
            ->join('LEFT JOIN projects p ON p.id = x.project_id')
            ->where('x.company_id = ?', Tenant::id())
            ->where('x.deleted_at IS NULL')
            ->search(['x.fund_number', 'x.contributor_name', 'x.contributor_phone', 'x.purpose', 'x.reference'], $f['q'] ?? '')
            ->whereIf($f['contributor'] ?? '', 'x.user_id = ?', (int) ($f['contributor'] ?? 0))
            ->whereIf($f['type'] ?? '', 'x.fund_type = ?')
            ->whereIf($f['status'] ?? '', 'x.status = ?')
            ->whereIf($f['project'] ?? '', 'x.project_id = ?', (int) ($f['project'] ?? 0))
            ->dateRange('x.contribution_date', $f['from'] ?? null, $f['to'] ?? null)
            ->sort($f['sort'] ?? null, $f['dir'] ?? null, ['number' => 'x.id', 'date' => 'x.contribution_date', 'amount' => 'x.amount', 'contributor' => 'x.contributor_name'], 'x.contribution_date DESC, x.id DESC');
    }

    public static function find(int $id): array
    {
        $x = Database::fetch(
            'SELECT x.*, m.name AS method_name, p.project_name, u.name AS created_by_name
             FROM fund_contributions x LEFT JOIN master_data m ON m.id = x.payment_method_id
             LEFT JOIN projects p ON p.id = x.project_id LEFT JOIN users u ON u.id = x.created_by
             WHERE x.id = ? AND x.company_id = ? AND x.deleted_at IS NULL',
            [$id, Tenant::id()]
        );
        if (!$x) {
            throw new HttpException(404);
        }
        return $x;
    }

    /** Contributor dropdown: active users, directors first, labelled with their roles. */
    public static function contributorOptions(): array
    {
        return Database::pairs(
            "SELECT u.id, CONCAT(u.name, COALESCE(CONCAT(' (', GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ', '), ')'), ''))
             FROM users u LEFT JOIN user_roles ur ON ur.user_id = u.id LEFT JOIN roles r ON r.id = ur.role_id AND r.slug <> 'super_admin'
             WHERE u.company_id = ? AND u.deleted_at IS NULL AND u.status = 'active'
             GROUP BY u.id, u.name ORDER BY MAX(r.slug = 'director') DESC, u.name",
            [Tenant::id()]
        );
    }

    public static function save(array $input, ?int $id = null): int
    {
        $old = $id ? self::find($id) : null;
        if ($old && $old['status'] === 'cancelled') {
            throw ValidationException::rule('Cancelled contributions cannot be edited.');
        }
        $data = Validator::make($input, [
            'user_id'           => ['nullable', static fn($v) => Tenant::owns('users', $v, 'deleted_at IS NULL') ? null : 'Please select a valid contributor.'],
            'contributor_name'  => 'nullable|max:150',
            'contributor_phone' => 'nullable|phone',
            'fund_type'         => 'required|in:' . implode(',', array_keys(self::TYPES)),
            'project_id'        => ['nullable', ProjectService::rule()],
            'purpose'           => 'nullable|max:255',
            'contribution_date' => 'required|date',
            'amount'            => 'required|money',
            'payment_method_id' => ['nullable', MasterDataService::rule('payment_method')],
            'reference'         => 'nullable|max:150',
            'notes'             => 'nullable|max:3000',
        ], ['user_id' => 'Contributor', 'fund_type' => 'Fund type', 'payment_method_id' => 'Payment method', 'project_id' => 'Project']);
        if (Money::toCents($data['amount']) <= 0) {
            throw new ValidationException(['amount' => 'Amount must be greater than zero.']);
        }
        // A user contributor's name/phone are snapshotted so receipts stay correct if the user changes later.
        if ($data['user_id']) {
            $u = Database::fetch('SELECT name, phone FROM users WHERE id = ?', [$data['user_id']]);
            $data['contributor_name'] = $u['name'];
            $data['contributor_phone'] = $data['contributor_phone'] ?: $u['phone'];
        } elseif (!$data['contributor_name']) {
            throw new ValidationException(['contributor_name' => 'Select a director/user or enter the contributor name.']);
        }
        if ($file = Request::file('attachment')) {
            $st = FileUploadService::store($file, ['document', 'image'], 'attachment');
            $data += ['attachment' => $st['stored_name'], 'attachment_name' => $st['original_name'], 'attachment_mime' => $st['mime']];
        }

        if ($id) {
            Database::update('fund_contributions', $data, 'id = ? AND company_id = ?', [$id, Tenant::id()]);
            if (isset($data['attachment']) && $old['attachment']) {
                FileUploadService::delete($old['attachment']);
            }
            AuditLogService::log('UPDATE_FUND', 'fund_contributions', $id, $old, $data);
            return $id;
        }
        return Database::transaction(static function () use ($data) {
            $data += [
                'company_id' => Tenant::id(), 'fund_number' => NumberGeneratorService::next('fund'),
                'status' => 'received', 'created_by' => AuthService::id(),
            ];
            $id = Database::insert('fund_contributions', $data);
            AuditLogService::log('CREATE_FUND', 'fund_contributions', $id, null, $data);
            return $id;
        });
    }

    public static function cancel(int $id, ?string $reason): void
    {
        $x = self::find($id);
        if ($x['status'] === 'cancelled') {
            throw ValidationException::rule('Already cancelled.');
        }
        Database::update('fund_contributions', ['status' => 'cancelled', 'notes' => trim(($x['notes'] ?? '') . "\n[Cancelled] " . ($reason ?? ''))], 'id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('CANCEL_FUND', 'fund_contributions', $id, ['status' => $x['status']], ['status' => 'cancelled', 'reason' => $reason]);
    }

    /** Received totals per contributor for the filtered set. */
    public static function byContributor(array $f): array
    {
        return self::query($f)->where("x.status = 'received'")
            ->select('x.user_id, x.contributor_name, COUNT(*) AS n, SUM(x.amount) AS total')
            ->groupBy('x.user_id, x.contributor_name')->orderBy('total DESC')->get(50);
    }

    /** Received total and per-type breakdown for one user (shown on the user profile). */
    public static function summaryForUser(int $userId): array
    {
        $rows = Database::pairs(
            "SELECT fund_type, SUM(amount) FROM fund_contributions WHERE company_id = ? AND user_id = ? AND status = 'received' AND deleted_at IS NULL GROUP BY fund_type",
            [Tenant::id(), $userId]
        );
        return ['by_type' => $rows, 'total' => Money::fromCents(array_sum(array_map(static fn($v) => Money::toCents((string) $v), $rows)))];
    }
}
