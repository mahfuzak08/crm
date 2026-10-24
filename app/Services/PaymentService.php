<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Money;
use App\Core\Query;
use App\Core\ValidationException;
use App\Core\Validator;

/**
 * Payments are applied to invoices. Rules (server-side):
 *  - amount > 0 and <= invoice due (no overpayment)
 *  - not for draft/cancelled invoices
 *  - invoice row locked FOR UPDATE (concurrent payments are serialised)
 *  - idempotency key prevents duplicate submits
 *  - cancellation (reversal) instead of deletion
 */
final class PaymentService
{
    public static function query(array $f): Query
    {
        return Query::table('payments p')
            ->select('p.*, c.name AS customer_name, c.customer_code, i.invoice_number, m.name AS method_name, u.name AS received_by_name, pr.project_name')
            ->join('JOIN customers c ON c.id = p.customer_id')
            ->join('JOIN invoices i ON i.id = p.invoice_id')
            ->join('JOIN master_data m ON m.id = p.payment_method_id')
            ->join('LEFT JOIN users u ON u.id = p.received_by')
            ->join('LEFT JOIN projects pr ON pr.id = p.project_id')
            ->where('p.company_id = ?', Tenant::id())
            ->search(['p.payment_number', 'p.reference', 'c.name', 'c.phone', 'i.invoice_number'], $f['q'] ?? '')
            ->whereIf($f['status'] ?? '', 'p.status = ?')
            ->whereIf($f['method'] ?? '', 'p.payment_method_id = ?', (int) ($f['method'] ?? 0))
            ->whereIf($f['customer'] ?? '', 'p.customer_id = ?', (int) ($f['customer'] ?? 0))
            ->whereIf($f['project'] ?? '', 'p.project_id = ?', (int) ($f['project'] ?? 0))
            ->dateRange('p.payment_date', $f['from'] ?? null, $f['to'] ?? null)
            ->sort($f['sort'] ?? null, $f['dir'] ?? null, ['number' => 'p.id', 'date' => 'p.payment_date', 'amount' => 'p.amount'], 'p.id DESC');
    }

    public static function find(int $id): array
    {
        $p = Database::fetch(
            'SELECT p.*, c.name AS customer_name, c.customer_code, c.phone AS customer_phone, c.address AS customer_address,
                    i.invoice_number, i.grand_total AS invoice_total, i.due_amount AS invoice_due, i.paid_amount AS invoice_paid,
                    m.name AS method_name, u.name AS received_by_name, pr.project_name, cu.name AS cancelled_by_name
             FROM payments p JOIN customers c ON c.id = p.customer_id JOIN invoices i ON i.id = p.invoice_id
             JOIN master_data m ON m.id = p.payment_method_id LEFT JOIN users u ON u.id = p.received_by
             LEFT JOIN projects pr ON pr.id = p.project_id LEFT JOIN users cu ON cu.id = p.cancelled_by
             WHERE p.id = ? AND p.company_id = ?',
            [$id, Tenant::id()]
        );
        if (!$p) {
            throw new HttpException(404);
        }
        return $p;
    }

    public static function create(array $input): int
    {
        $data = Validator::make($input, [
            'invoice_id'        => ['required', 'id'],
            'amount'            => 'required|money',
            'payment_date'      => 'required|date',
            'payment_method_id' => ['required', MasterDataService::rule('payment_method', true)],
            'reference'         => 'nullable|max:150',
            'notes'             => 'nullable|max:2000',
            'idempotency_key'   => ['nullable', static fn($v) => preg_match('/^[a-f0-9]{32}$/', (string) $v) ? null : 'Invalid form token.'],
        ], ['invoice_id' => 'Invoice', 'payment_method_id' => 'Payment method']);

        $amount = Money::toCents($data['amount']);
        if ($amount <= 0) {
            throw new ValidationException(['amount' => 'Payment amount must be greater than zero.']);
        }
        if ($data['payment_date'] > today()) {
            throw new ValidationException(['payment_date' => 'Payment date cannot be in the future.']);
        }

        return Database::transaction(static function () use ($data, $amount) {
            // Lock the invoice row before any other read (serialises concurrent payments).
            Database::fetch('SELECT id FROM invoices WHERE id = ? AND company_id = ? FOR UPDATE', [$data['invoice_id'], Tenant::id()]);
            // Duplicate submit protection.
            if ($data['idempotency_key']) {
                $existing = Database::value('SELECT id FROM payments WHERE company_id = ? AND idempotency_key = ?', [Tenant::id(), $data['idempotency_key']]);
                if ($existing) {
                    return (int) $existing;
                }
            }
            $inv = Database::fetch('SELECT * FROM invoices WHERE id = ? AND company_id = ? AND deleted_at IS NULL FOR UPDATE', [$data['invoice_id'], Tenant::id()]);
            if (!$inv) {
                throw new ValidationException(['invoice_id' => 'Invalid invoice.']);
            }
            if ($inv['status'] === 'cancelled') {
                throw new ValidationException(['invoice_id' => 'Cannot accept payment for a cancelled invoice.']);
            }
            if ($inv['status'] === 'draft') {
                throw new ValidationException(['invoice_id' => 'Issue the invoice before receiving payment.']);
            }
            $paid = Money::toCents((string) Database::value("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ? AND status = 'confirmed' LOCK IN SHARE MODE", [$inv['id']]));
            $due = Money::toCents($inv['grand_total']) - $paid;
            if ($amount > $due) {
                throw new ValidationException(['amount' => 'Amount exceeds the invoice due of ' . Money::format(Money::fromCents(max(0, $due))) . '.']);
            }

            $row = [
                'company_id' => Tenant::id(), 'payment_number' => NumberGeneratorService::next('payment'),
                'customer_id' => $inv['customer_id'], 'invoice_id' => $inv['id'], 'project_id' => $inv['project_id'],
                'payment_date' => $data['payment_date'], 'amount' => Money::fromCents($amount),
                'payment_method_id' => $data['payment_method_id'], 'reference' => $data['reference'], 'notes' => $data['notes'],
                'received_by' => AuthService::id(), 'status' => 'confirmed', 'idempotency_key' => $data['idempotency_key'],
                'created_by' => AuthService::id(),
            ];
            $id = Database::insert('payments', $row);
            InvoiceService::recalc((int) $inv['id']);
            AuditLogService::log('CREATE_PAYMENT', 'payments', $id, null, $row);
            ActivityService::system('customer', (int) $inv['customer_id'], 'payment', sprintf('Payment %s received: %s against %s', $row['payment_number'], Money::format($row['amount']), $inv['invoice_number']));
            return $id;
        });
    }

    public static function cancel(int $id, ?string $reason): void
    {
        Database::transaction(static function () use ($id, $reason) {
            $invoiceId = Database::value('SELECT invoice_id FROM payments WHERE id = ? AND company_id = ?', [$id, Tenant::id()]) ?? throw new HttpException(404);
            Database::fetch('SELECT id FROM invoices WHERE id = ? FOR UPDATE', [$invoiceId]);
            $p = Database::fetch('SELECT * FROM payments WHERE id = ? FOR UPDATE', [$id]);
            if ($p['status'] === 'cancelled') {
                throw ValidationException::rule('Payment is already cancelled.');
            }
            Database::update('payments', [
                'status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => AuthService::id(),
                'cancel_reason' => $reason ? mb_substr($reason, 0, 255) : null,
            ], 'id = ?', [$id]);
            InvoiceService::recalc((int) $p['invoice_id']);
            AuditLogService::log('CANCEL_PAYMENT', 'payments', $id, ['status' => 'confirmed', 'amount' => $p['amount']], ['status' => 'cancelled', 'reason' => $reason]);
            ActivityService::system('customer', (int) $p['customer_id'], 'payment', 'Payment ' . $p['payment_number'] . ' cancelled' . ($reason ? ': ' . $reason : ''));
        });
    }

    /** Only non-financial fields are editable. */
    public static function updateMeta(int $id, array $input): void
    {
        $p = self::find($id);
        $data = Validator::make($input, ['reference' => 'nullable|max:150', 'notes' => 'nullable|max:2000']);
        Database::update('payments', $data, 'id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('UPDATE_PAYMENT', 'payments', $id, array_intersect_key($p, $data), $data);
    }
}
