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
 * Share sales. Availability is checked with the project row locked so two
 * concurrent sales can never oversell. Totals come from PricingService.
 */
final class SaleService
{
    public static function query(array $f): Query
    {
        return Query::table('share_sales s')
            ->select('s.*, c.name AS customer_name, c.customer_code, p.project_name, p.project_code, sp.name AS package_name, u.name AS sales_person, i.invoice_number')
            ->join('JOIN customers c ON c.id = s.customer_id')
            ->join('JOIN projects p ON p.id = s.project_id')
            ->join('LEFT JOIN share_packages sp ON sp.id = s.package_id')
            ->join('LEFT JOIN users u ON u.id = s.sales_person_id')
            ->join('LEFT JOIN invoices i ON i.id = s.invoice_id')
            ->where('s.company_id = ?', Tenant::id())
            ->search(['s.sale_number', 'c.name', 'c.phone', 'c.customer_code'], $f['q'] ?? '')
            ->whereIf($f['status'] ?? '', 's.status = ?')
            ->whereIf($f['project'] ?? '', 's.project_id = ?', (int) ($f['project'] ?? 0))
            ->whereIf($f['customer'] ?? '', 's.customer_id = ?', (int) ($f['customer'] ?? 0))
            ->whereIf($f['sales_person'] ?? '', 's.sales_person_id = ?', (int) ($f['sales_person'] ?? 0))
            ->dateRange('s.sale_date', $f['from'] ?? null, $f['to'] ?? null)
            ->sort($f['sort'] ?? null, $f['dir'] ?? null, ['number' => 's.id', 'date' => 's.sale_date', 'total' => 's.grand_total', 'shares' => 's.shares'], 's.id DESC');
    }

    public static function find(int $id): array
    {
        $s = Database::fetch(
            'SELECT s.*, c.name AS customer_name, c.customer_code, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address,
                    c.father_name, c.nid, p.project_name, p.project_code, p.location, p.share_unit, sp.name AS package_name, sp.shares_count,
                    u.name AS sales_person, i.invoice_number, o.offer_number, cb.name AS created_by_name
             FROM share_sales s JOIN customers c ON c.id = s.customer_id JOIN projects p ON p.id = s.project_id
             LEFT JOIN share_packages sp ON sp.id = s.package_id LEFT JOIN users u ON u.id = s.sales_person_id
             LEFT JOIN invoices i ON i.id = s.invoice_id LEFT JOIN offers o ON o.id = s.offer_id LEFT JOIN users cb ON cb.id = s.created_by
             WHERE s.id = ? AND s.company_id = ?',
            [$id, Tenant::id()]
        );
        if (!$s) {
            throw new HttpException(404);
        }
        return $s;
    }

    public static function create(array $input): int
    {
        $data = Validator::make($input, [
            'customer_id'      => ['required', CustomerService::rule()],
            'project_id'       => ['required', ProjectService::rule()],
            'package_id'       => 'nullable|id',
            'quantity'         => 'required|integer|gte:1',
            'discount'         => 'nullable|money',
            'registration_fee' => 'nullable|money',
            'development_fee'  => 'nullable|money',
            'other_fee'        => 'nullable|money',
            'sale_date'        => 'required|date',
            'sales_person_id'  => ['nullable', static fn($v) => Tenant::owns('users', $v, 'deleted_at IS NULL') ? null : 'Invalid sales person.'],
            'status'           => 'required|in:confirmed,reserved',
            'notes'            => 'nullable|max:2000',
            'offer_id'         => ['nullable', static fn($v) => Tenant::owns('offers', $v, "deleted_at IS NULL AND status NOT IN ('converted', 'rejected')") ? null : 'Invalid or already converted offer.'],
            'initial_payment'  => 'nullable|money',
            'payment_method_id'=> ['nullable', MasterDataService::rule('payment_method', true)],
            'payment_reference'=> 'nullable|max:150',
            'idempotency_key'  => ['nullable', static fn($v) => preg_match('/^[a-f0-9]{32}$/', (string) $v) ? null : 'Invalid form token.'],
        ], ['customer_id' => 'Customer', 'project_id' => 'Project', 'sales_person_id' => 'Sales person', 'payment_method_id' => 'Payment method']);

        if ($data['offer_id']) {
            $offer = OfferService::find((int) $data['offer_id']); // visibility check
            if ((int) $offer['customer_id'] !== (int) $data['customer_id'] || (int) $offer['project_id'] !== (int) $data['project_id']) {
                throw new ValidationException(['offer_id' => 'The offer does not match this customer and project.']);
            }
        }
        $initial = Money::toCents($data['initial_payment'] ?? '0');
        if ($initial > 0 && $data['status'] !== 'confirmed') {
            throw new ValidationException(['initial_payment' => 'Payments can only be received on confirmed sales.']);
        }
        if ($initial > 0 && !$data['payment_method_id']) {
            throw new ValidationException(['payment_method_id' => 'Select a payment method for the initial payment.']);
        }
        if ($initial > 0 && !AuthService::can('payments.create')) {
            throw new ValidationException(['initial_payment' => 'You are not allowed to receive payments.']);
        }

        return Database::transaction(static function () use ($data, $initial) {
            // Lock the project row → serialises concurrent sales of the same project.
            ShareService::committed((int) $data['project_id'], true); // lock project row first
            $calc = PricingService::calculate($data);
            ShareService::assertAvailable((int) $data['project_id'], $calc['shares']);
            if ($initial > Money::toCents($calc['total'])) {
                throw new ValidationException(['initial_payment' => 'Initial payment cannot exceed the sale total.']);
            }

            $sale = [
                'company_id' => Tenant::id(), 'sale_number' => NumberGeneratorService::next('sale'),
                'customer_id' => $data['customer_id'], 'project_id' => $data['project_id'], 'package_id' => $calc['package']['id'] ?? null,
                'quantity' => $data['quantity'], 'shares' => $calc['shares'], 'unit_price' => $calc['unit_price'],
                'subtotal' => $calc['subtotal'], 'discount' => $calc['discount'], 'registration_fee' => $calc['registration_fee'],
                'development_fee' => $calc['development_fee'], 'other_fee' => $calc['other_fee'], 'grand_total' => $calc['total'],
                'paid_amount' => '0.00', 'due_amount' => $calc['total'], 'sale_date' => $data['sale_date'],
                'sales_person_id' => $data['sales_person_id'] ?? AuthService::id(), 'offer_id' => $data['offer_id'],
                'status' => $data['status'], 'notes' => $data['notes'], 'created_by' => AuthService::id(),
            ];
            $id = Database::insert('share_sales', $sale);
            $sale['id'] = $id;
            ShareService::ledger((int) $data['project_id'], $data['status'] === 'reserved' ? 'reserve' : 'sale', $calc['shares'], $id, (int) $data['customer_id']);

            if ($data['status'] === 'confirmed' && SettingsService::get('sales.auto_invoice', '1') === '1') {
                $invoiceId = InvoiceService::createForSale($sale, $calc);
                Database::update('share_sales', ['invoice_id' => $invoiceId], 'id = ?', [$id]);
                if ($initial > 0) {
                    PaymentService::create([
                        'invoice_id' => (string) $invoiceId, 'amount' => Money::fromCents($initial), 'payment_date' => $data['sale_date'] > today() ? today() : $data['sale_date'],
                        'payment_method_id' => (string) $data['payment_method_id'], 'reference' => $data['payment_reference'],
                        'notes' => 'Initial payment for ' . $sale['sale_number'], 'idempotency_key' => $data['idempotency_key'],
                    ]);
                }
            }
            if ($data['offer_id']) {
                Database::update('offers', ['status' => 'converted', 'sale_id' => $id], 'id = ? AND company_id = ?', [$data['offer_id'], Tenant::id()]);
            }
            AuditLogService::log('CREATE_SHARE_SALE', 'share_sales', $id, null, $sale);
            ActivityService::system('customer', (int) $data['customer_id'], 'note', sprintf('%s %d shares of %s (%s), total %s', $data['status'] === 'reserved' ? 'Reserved' : 'Purchased', $calc['shares'], $calc['project']['project_name'], $sale['sale_number'], Money::format($calc['total'])));
            return $id;
        });
    }

    /** Reserved -> confirmed (creates the invoice). */
    public static function confirm(int $id): void
    {
        Database::transaction(static function () use ($id) {
            $sale = Database::fetch('SELECT * FROM share_sales WHERE id = ? AND company_id = ? FOR UPDATE', [$id, Tenant::id()]) ?? throw new HttpException(404);
            if ($sale['status'] !== 'reserved') {
                throw ValidationException::rule('Only reserved sales can be confirmed.');
            }
            Database::update('share_sales', ['status' => 'confirmed'], 'id = ?', [$id]);
            $sale['status'] = 'confirmed';
            if (SettingsService::get('sales.auto_invoice', '1') === '1' && !$sale['invoice_id']) {
                $project = Database::fetch('SELECT * FROM projects WHERE id = ?', [$sale['project_id']]);
                $package = $sale['package_id'] ? Database::fetch('SELECT * FROM share_packages WHERE id = ?', [$sale['package_id']]) : null;
                $calc = [
                    'project' => $project, 'package' => $package, 'shares' => (int) $sale['shares'], 'unit_price' => $sale['unit_price'],
                    'subtotal' => $sale['subtotal'], 'discount' => $sale['discount'], 'registration_fee' => $sale['registration_fee'],
                    'development_fee' => $sale['development_fee'], 'other_fee' => $sale['other_fee'], 'total' => $sale['grand_total'],
                ];
                $invoiceId = InvoiceService::createForSale($sale, $calc);
                Database::update('share_sales', ['invoice_id' => $invoiceId], 'id = ?', [$id]);
            }
            ShareService::ledger((int) $sale['project_id'], 'confirm', (int) $sale['shares'], $id, (int) $sale['customer_id']);
            AuditLogService::log('CONFIRM_SHARE_SALE', 'share_sales', $id, ['status' => 'reserved'], ['status' => 'confirmed']);
        });
    }

    /** Cancel sale: releases shares, cancels invoice. Blocked while payments exist. */
    public static function cancel(int $id, ?string $reason): void
    {
        Database::transaction(static function () use ($id, $reason) {
            $sale = Database::fetch('SELECT * FROM share_sales WHERE id = ? AND company_id = ? FOR UPDATE', [$id, Tenant::id()]) ?? throw new HttpException(404);
            if ($sale['status'] === 'cancelled') {
                throw ValidationException::rule('Sale is already cancelled.');
            }
            if ($sale['invoice_id']) {
                InvoiceService::cancel((int) $sale['invoice_id'], 'Sale ' . $sale['sale_number'] . ' cancelled', true);
            }
            Database::update('share_sales', [
                'status' => 'cancelled', 'due_amount' => '0.00', 'cancelled_at' => now(), 'cancelled_by' => AuthService::id(),
                'cancel_reason' => $reason ? mb_substr($reason, 0, 255) : null,
            ], 'id = ?', [$id]);
            ShareService::ledger((int) $sale['project_id'], $sale['status'] === 'reserved' ? 'release' : 'cancel', (int) $sale['shares'], $id, (int) $sale['customer_id'], $reason);
            AuditLogService::log('CANCEL_SHARE_SALE', 'share_sales', $id, ['status' => $sale['status']], ['status' => 'cancelled', 'reason' => $reason]);
            ActivityService::system('customer', (int) $sale['customer_id'], 'note', 'Share sale ' . $sale['sale_number'] . ' cancelled' . ($reason ? ': ' . $reason : ''));
        });
    }
}
