<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Money;
use App\Core\Query;
use App\Core\ValidationException;
use App\Core\Validator;

final class InvoiceService
{
    public const STATUSES = ['draft', 'unpaid', 'partial', 'paid', 'overdue', 'cancelled'];

    public static function query(array $f): Query
    {
        self::refreshOverdue();
        return Query::table('invoices i')
            ->select('i.*, c.name AS customer_name, c.customer_code, p.project_name')
            ->join('JOIN customers c ON c.id = i.customer_id')
            ->join('LEFT JOIN projects p ON p.id = i.project_id')
            ->where('i.company_id = ?', Tenant::id())
            ->where('i.deleted_at IS NULL')
            ->search(['i.invoice_number', 'c.name', 'c.phone', 'c.customer_code'], $f['q'] ?? '')
            ->whereIf($f['status'] ?? '', 'i.status = ?')
            ->whereIf($f['customer'] ?? '', 'i.customer_id = ?', (int) ($f['customer'] ?? 0))
            ->whereIf($f['project'] ?? '', 'i.project_id = ?', (int) ($f['project'] ?? 0))
            ->dateRange('i.invoice_date', $f['from'] ?? null, $f['to'] ?? null)
            ->sort($f['sort'] ?? null, $f['dir'] ?? null, ['number' => 'i.id', 'date' => 'i.invoice_date', 'due' => 'i.due_amount', 'total' => 'i.grand_total', 'due_date' => 'i.due_date'], 'i.id DESC');
    }

    public static function find(int $id): array
    {
        $inv = Database::fetch(
            'SELECT i.*, c.name AS customer_name, c.customer_code, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address,
                    p.project_name, p.project_code, s.sale_number, u.name AS created_by_name
             FROM invoices i JOIN customers c ON c.id = i.customer_id LEFT JOIN projects p ON p.id = i.project_id
             LEFT JOIN share_sales s ON s.id = i.sale_id LEFT JOIN users u ON u.id = i.created_by
             WHERE i.id = ? AND i.company_id = ? AND i.deleted_at IS NULL',
            [$id, Tenant::id()]
        );
        if (!$inv) {
            throw new HttpException(404);
        }
        $inv['items'] = Database::fetchAll('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order, id', [$id]);
        return $inv;
    }

    /** Validate items + header and compute all totals server-side. */
    private static function build(array $input): array
    {
        $data = Validator::make($input, [
            'customer_id'  => ['required', CustomerService::rule()],
            'project_id'   => ['nullable', ProjectService::rule()],
            'invoice_date' => 'required|date',
            'due_date'     => 'nullable|date|after_or_equal:invoice_date',
            'discount'     => 'nullable|money',
            'tax_percent'  => ['nullable', static fn($v) => preg_match('/^\d{1,2}(\.\d{1,2})?$|^100(\.0{1,2})?$/', (string) $v) ? null : 'Tax % must be between 0 and 100.'],
            'other_charge' => 'nullable|money',
            'notes'        => 'nullable|max:2000',
            'terms'        => 'nullable|max:3000',
        ], ['customer_id' => 'Customer', 'project_id' => 'Project']);

        $raw = is_array($input['items'] ?? null) ? $input['items'] : [];
        $desc = (array) ($raw['desc'] ?? []);
        $qtys = (array) ($raw['qty'] ?? []);
        $prices = (array) ($raw['price'] ?? []);
        $items = [];
        $subtotal = 0;
        foreach ($desc as $i => $d) {
            $d = trim((string) $d);
            $q = trim((string) ($qtys[$i] ?? ''));
            $p = trim((string) ($prices[$i] ?? ''));
            if ($d === '' && $q === '' && $p === '') {
                continue;
            }
            if ($d === '' || mb_strlen($d) > 255) {
                throw new ValidationException(['items' => 'Each line needs a description (max 255 characters).']);
            }
            if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $q) || Money::toCents($q) <= 0) {
                throw new ValidationException(['items' => "Invalid quantity on line \"$d\"."]);
            }
            if (!Money::isValid($p)) {
                throw new ValidationException(['items' => "Invalid unit price on line \"$d\"."]);
            }
            $qh = Money::toCents($q);            // quantity in hundredths
            $pc = Money::toCents($p);
            if ($pc > 0 && $qh > intdiv(Money::MAX * 100, $pc)) {
                throw new ValidationException(['items' => "Line \"$d\" amount is too large."]);
            }
            $amount = intdiv($qh * $pc + 50, 100); // round half up
            Money::assertStorable($amount, 'items');
            $subtotal += $amount;
            $items[] = ['description' => $d, 'quantity' => Money::fromCents($qh), 'unit_price' => Money::fromCents($pc), 'amount' => Money::fromCents($amount), 'sort_order' => count($items)];
        }
        if (!$items) {
            throw new ValidationException(['items' => 'Add at least one line item.']);
        }
        $discount = Money::toCents($data['discount'] ?? '0');
        if ($discount > $subtotal) {
            throw new ValidationException(['discount' => 'Discount cannot exceed the subtotal.']);
        }
        $taxPct = (string) ($data['tax_percent'] ?? '0');
        $tax = Money::percent($subtotal - $discount, $taxPct === '' ? '0' : $taxPct);
        $other = Money::toCents($data['other_charge'] ?? '0');
        $grand = $subtotal - $discount + $tax + $other;
        Money::assertStorable($subtotal, 'items');
        Money::assertStorable($grand, 'items');

        $data['due_date'] ??= date('Y-m-d', strtotime($data['invoice_date'] . ' +' . (int) SettingsService::get('invoice.default_due_days', 30) . ' days'));
        return [$data + [
            'subtotal' => Money::fromCents($subtotal), 'discount' => Money::fromCents($discount), 'tax_percent' => $taxPct ?: '0',
            'tax' => Money::fromCents($tax), 'other_charge' => Money::fromCents($other), 'grand_total' => Money::fromCents($grand),
        ], $items];
    }

    public static function create(array $input, bool $draft = false): int
    {
        [$data, $items] = self::build($input);
        return Database::transaction(static function () use ($data, $items, $draft) {
            $data += [
                'company_id' => Tenant::id(), 'invoice_number' => NumberGeneratorService::next('invoice'),
                'paid_amount' => '0.00', 'due_amount' => $data['grand_total'], 'status' => $draft ? 'draft' : 'unpaid',
                'created_by' => AuthService::id(),
            ];
            $id = Database::insert('invoices', $data);
            self::insertItems($id, $items);
            self::recalc($id);
            AuditLogService::log('CREATE_INVOICE', 'invoices', $id, null, $data);
            return $id;
        });
    }

    /** Only drafts may be edited — issued invoices are immutable (cancel & re-issue instead). */
    public static function update(int $id, array $input): void
    {
        [$data, $items] = self::build($input);
        Database::transaction(static function () use ($id, $data, $items) {
            $old = Database::fetch('SELECT * FROM invoices WHERE id = ? AND company_id = ? AND deleted_at IS NULL FOR UPDATE', [$id, Tenant::id()]) ?? throw new HttpException(404);
            if ($old['status'] !== 'draft' || $old['sale_id']) {
                throw ValidationException::rule('Only draft invoices can be edited.');
            }
            $data['due_amount'] = $data['grand_total'];
            Database::update('invoices', $data, 'id = ?', [$id]);
            Database::run('DELETE FROM invoice_items WHERE invoice_id = ?', [$id]);
            self::insertItems($id, $items);
            AuditLogService::log('UPDATE_INVOICE', 'invoices', $id, $old, $data);
        });
    }

    public static function issue(int $id): void
    {
        Database::transaction(static function () use ($id) {
            $inv = Database::fetch('SELECT * FROM invoices WHERE id = ? AND company_id = ? FOR UPDATE', [$id, Tenant::id()]) ?? throw new HttpException(404);
            if ($inv['status'] !== 'draft') {
                throw ValidationException::rule('Invoice is already issued.');
            }
            Database::update('invoices', ['status' => 'unpaid'], 'id = ?', [$id]);
            self::recalc($id);
            AuditLogService::log('ISSUE_INVOICE', 'invoices', $id);
        });
    }

    /** Invoice for a confirmed share sale (called inside SaleService transaction). */
    public static function createForSale(array $sale, array $calc): int
    {
        $items = [];
        $pkg = $calc['package'];
        $items[] = [
            'description' => sprintf('Share purchase — %s: %s', $calc['project']['project_name'], $pkg ? $pkg['name'] . ' (' . $calc['shares'] . ' shares)' : $calc['shares'] . ' shares'),
            'quantity' => $sale['quantity'] . '.00', 'unit_price' => $calc['unit_price'], 'amount' => $calc['subtotal'], 'sort_order' => 0,
        ];
        foreach (['registration_fee' => 'Registration fee', 'development_fee' => 'Development fee', 'other_fee' => 'Other charges'] as $k => $label) {
            if (Money::toCents($calc[$k]) > 0) {
                $items[] = ['description' => $label, 'quantity' => '1.00', 'unit_price' => $calc[$k], 'amount' => $calc[$k], 'sort_order' => count($items)];
            }
        }
        $subtotal = array_sum(array_map(static fn($i) => Money::toCents($i['amount']), $items));
        $grand = $subtotal - Money::toCents($calc['discount']);
        $data = [
            'company_id' => Tenant::id(), 'invoice_number' => NumberGeneratorService::next('invoice'),
            'customer_id' => $sale['customer_id'], 'project_id' => $sale['project_id'], 'sale_id' => $sale['id'],
            'invoice_date' => $sale['sale_date'],
            'due_date' => date('Y-m-d', strtotime($sale['sale_date'] . ' +' . (int) SettingsService::get('invoice.default_due_days', 30) . ' days')),
            'subtotal' => Money::fromCents($subtotal), 'discount' => $calc['discount'], 'tax_percent' => '0', 'tax' => '0.00', 'other_charge' => '0.00',
            'grand_total' => Money::fromCents($grand), 'paid_amount' => '0.00', 'due_amount' => Money::fromCents($grand),
            'status' => 'unpaid', 'terms' => SettingsService::get('invoice.terms'), 'notes' => 'Share sale ' . $sale['sale_number'],
            'created_by' => AuthService::id(),
        ];
        if ($grand !== Money::toCents($calc['total'])) {
            throw new \LogicException('Invoice total mismatch');
        }
        $id = Database::insert('invoices', $data);
        self::insertItems($id, $items);
        AuditLogService::log('CREATE_INVOICE', 'invoices', $id, null, $data);
        return $id;
    }

    /** Cancel — allowed only when there are no confirmed payments. */
    public static function cancel(int $id, ?string $reason, bool $fromSale = false): void
    {
        Database::transaction(static function () use ($id, $reason, $fromSale) {
            $inv = Database::fetch('SELECT * FROM invoices WHERE id = ? AND company_id = ? AND deleted_at IS NULL FOR UPDATE', [$id, Tenant::id()]) ?? throw new HttpException(404);
            if ($inv['status'] === 'cancelled') {
                return;
            }
            if ($inv['sale_id'] && !$fromSale) {
                throw ValidationException::rule('This invoice belongs to a share sale. Cancel the sale instead.');
            }
            if (Database::value("SELECT 1 FROM payments WHERE invoice_id = ? AND status = 'confirmed' LIMIT 1", [$id])) {
                throw ValidationException::rule('This invoice has confirmed payments. Cancel (reverse) the payments first.');
            }
            Database::update('invoices', [
                'status' => 'cancelled', 'due_amount' => '0.00', 'cancelled_at' => now(), 'cancelled_by' => AuthService::id(),
                'cancel_reason' => $reason ? mb_substr($reason, 0, 255) : null,
            ], 'id = ?', [$id]);
            AuditLogService::log('CANCEL_INVOICE', 'invoices', $id, ['status' => $inv['status']], ['status' => 'cancelled', 'reason' => $reason]);
        });
    }

    /**
     * Recompute paid/due/status from confirmed payments (source of truth) and
     * mirror into the linked share sale. Caller should hold the invoice lock.
     */
    public static function recalc(int $id): void
    {
        $inv = Database::fetch('SELECT * FROM invoices WHERE id = ?', [$id]);
        $paid = Money::toCents((string) Database::value("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ? AND status = 'confirmed'", [$id]));
        $grand = Money::toCents($inv['grand_total']);
        $due = $grand - $paid;
        $status = $inv['status'];
        if (!in_array($status, ['cancelled', 'draft'], true)) {
            $status = match (true) {
                $due <= 0 => 'paid',
                $inv['due_date'] && $inv['due_date'] < today() => 'overdue',
                $paid > 0 => 'partial',
                default => 'unpaid',
            };
        }
        $dueStr = $status === 'cancelled' ? '0.00' : Money::fromCents(max(0, $due));
        Database::update('invoices', ['paid_amount' => Money::fromCents($paid), 'due_amount' => $dueStr, 'status' => $status], 'id = ?', [$id]);
        if ($inv['sale_id']) {
            Database::update('share_sales', ['paid_amount' => Money::fromCents($paid), 'due_amount' => $dueStr], 'id = ?', [$inv['sale_id']]);
        }
    }

    /** Lazily flag overdue invoices (cheap indexed update; also run by cron). */
    public static function refreshOverdue(?int $companyId = null): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        Database::run(
            "UPDATE invoices SET status = 'overdue' WHERE company_id = ? AND status IN ('unpaid', 'partial') AND due_date < CURDATE() AND due_amount > 0 AND deleted_at IS NULL",
            [$companyId ?? Tenant::id()]
        );
    }

    private static function insertItems(int $invoiceId, array $items): void
    {
        foreach ($items as $it) {
            Database::insert('invoice_items', $it + ['invoice_id' => $invoiceId]);
        }
    }

    /** Open invoices for payment selection: [id => row]. */
    public static function openForCustomer(?int $customerId = null): array
    {
        $sql = "SELECT i.id, i.invoice_number, i.due_amount, i.grand_total, c.name AS customer_name FROM invoices i JOIN customers c ON c.id = i.customer_id
                WHERE i.company_id = ? AND i.deleted_at IS NULL AND i.status IN ('unpaid', 'partial', 'overdue') AND i.due_amount > 0";
        $params = [Tenant::id()];
        if ($customerId) {
            $sql .= ' AND i.customer_id = ?';
            $params[] = $customerId;
        }
        return Database::fetchAll($sql . ' ORDER BY i.invoice_date, i.id LIMIT 300', $params);
    }
}
