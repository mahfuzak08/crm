<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Money;
use App\Core\Query;
use App\Core\ValidationException;
use App\Core\Validator;

final class OfferService
{
    public const STATUSES = ['draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired', 'converted'];

    public static function query(array $f): Query
    {
        self::expireOld();
        $q = Query::table('offers o')
            ->select("o.*, p.project_name, COALESCE(c.name, l.name) AS recipient_name, IF(o.customer_id IS NULL, 'lead', 'customer') AS recipient_type, u.name AS created_by_name")
            ->join('JOIN projects p ON p.id = o.project_id')
            ->join('LEFT JOIN customers c ON c.id = o.customer_id')
            ->join('LEFT JOIN leads l ON l.id = o.lead_id')
            ->join('LEFT JOIN users u ON u.id = o.created_by')
            ->where('o.company_id = ?', Tenant::id())
            ->where('o.deleted_at IS NULL')
            ->search(['o.offer_number', 'c.name', 'l.name', 'c.phone', 'l.phone'], $f['q'] ?? '')
            ->whereIf($f['status'] ?? '', 'o.status = ?')
            ->whereIf($f['project'] ?? '', 'o.project_id = ?', (int) ($f['project'] ?? 0))
            ->dateRange('DATE(o.created_at)', $f['from'] ?? null, $f['to'] ?? null)
            ->sort($f['sort'] ?? null, $f['dir'] ?? null, ['number' => 'o.id', 'total' => 'o.total', 'valid' => 'o.valid_until'], 'o.id DESC');
        if (!AuthService::can('leads.view_all')) {
            $q->where('o.created_by = ?', (int) AuthService::id());
        }
        return $q;
    }

    public static function find(int $id): array
    {
        $o = Database::fetch(
            'SELECT o.*, p.project_name, p.project_code, p.location, p.share_unit, sp.name AS package_name, sp.shares_count,
                    c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, c.address AS customer_address, c.customer_code,
                    l.name AS lead_name, l.email AS lead_email, l.phone AS lead_phone, l.address AS lead_address, l.lead_code,
                    u.name AS created_by_name, s.sale_number
             FROM offers o JOIN projects p ON p.id = o.project_id LEFT JOIN share_packages sp ON sp.id = o.package_id
             LEFT JOIN customers c ON c.id = o.customer_id LEFT JOIN leads l ON l.id = o.lead_id
             LEFT JOIN users u ON u.id = o.created_by LEFT JOIN share_sales s ON s.id = o.sale_id
             WHERE o.id = ? AND o.company_id = ? AND o.deleted_at IS NULL',
            [$id, Tenant::id()]
        );
        if (!$o) {
            throw new HttpException(404);
        }
        if (!AuthService::can('leads.view_all') && (int) $o['created_by'] !== AuthService::id()) {
            throw new HttpException(404);
        }
        return $o;
    }

    public static function save(array $input, ?int $id = null): int
    {
        $old = $id ? self::find($id) : null;
        if ($old && !in_array($old['status'], ['draft', 'sent', 'viewed'], true)) {
            throw ValidationException::rule('Only draft or sent offers can be edited.');
        }
        $data = Validator::make($input, [
            'lead_id'          => ['nullable', static fn($v) => Tenant::owns('leads', $v, 'deleted_at IS NULL') ? null : 'Invalid lead.'],
            'customer_id'      => ['nullable', CustomerService::rule()],
            'project_id'       => ['required', ProjectService::rule()],
            'package_id'       => 'nullable|id',
            'quantity'         => 'required|integer|gte:1',
            'discount'         => 'nullable|money',
            'registration_fee' => 'nullable|money',
            'development_fee'  => 'nullable|money',
            'other_fee'        => 'nullable|money',
            'valid_until'      => 'nullable|date',
            'notes'            => 'nullable|max:3000',
            'terms'            => 'nullable|max:3000',
        ], ['lead_id' => 'Lead', 'customer_id' => 'Customer', 'project_id' => 'Project']);
        if (!$data['lead_id'] && !$data['customer_id']) {
            throw new ValidationException(['customer_id' => 'Select a customer or a lead.']);
        }
        if ($data['lead_id']) {
            LeadService::find((int) $data['lead_id']); // visibility
        }
        $calc = PricingService::calculate($data);
        $inv = ShareService::inventory((int) $data['project_id']);
        if ($calc['shares'] > $inv['available']) {
            throw new ValidationException(['quantity' => "Only {$inv['available']} shares are currently available."]);
        }
        $row = [
            'project_id' => $data['project_id'], 'lead_id' => $data['customer_id'] ? null : $data['lead_id'], 'customer_id' => $data['customer_id'],
            'package_id' => $calc['package']['id'] ?? null, 'quantity' => $data['quantity'], 'shares' => $calc['shares'],
            'unit_price' => $calc['unit_price'], 'subtotal' => $calc['subtotal'], 'discount' => $calc['discount'],
            'registration_fee' => $calc['registration_fee'], 'development_fee' => $calc['development_fee'], 'other_fee' => $calc['other_fee'],
            'total' => $calc['total'],
            'valid_until' => $data['valid_until'] ?? date('Y-m-d', strtotime('+' . (int) SettingsService::get('offer.valid_days', 15) . ' days')),
            'notes' => $data['notes'], 'terms' => $data['terms'] ?? SettingsService::get('offer.terms'),
        ];
        if ($id) {
            Database::update('offers', $row, 'id = ? AND company_id = ?', [$id, Tenant::id()]);
            AuditLogService::log('UPDATE_OFFER', 'offers', $id, $old, $row);
            return $id;
        }
        return Database::transaction(static function () use ($row) {
            $row += ['company_id' => Tenant::id(), 'offer_number' => NumberGeneratorService::next('offer'), 'status' => 'draft', 'created_by' => AuthService::id()];
            $newId = Database::insert('offers', $row);
            AuditLogService::log('CREATE_OFFER', 'offers', $newId, null, $row);
            return $newId;
        });
    }

    /** Email the offer (if possible) and mark as sent. Returns [emailed(bool), publicUrl]. */
    public static function send(int $id): array
    {
        $o = self::find($id);
        if (!in_array($o['status'], ['draft', 'sent', 'viewed'], true)) {
            throw ValidationException::rule('This offer can no longer be sent.');
        }
        $publicUrl = ShareLinkService::url('offer', $id, max(1, (int) ceil((strtotime((string) $o['valid_until']) - time()) / 86400) + 1));
        $email = $o['customer_email'] ?? $o['lead_email'];
        $name = $o['customer_name'] ?? $o['lead_name'];
        $emailed = false;
        if ($email && NotificationService::canEmail()) {
            $html = '<p>Dear ' . e($name) . ',</p><p>Please find our offer <strong>' . e($o['offer_number']) . '</strong> for <strong>' . e($o['project_name']) . '</strong>: '
                . (int) $o['shares'] . ' shares, total <strong>' . e(money($o['total'])) . '</strong>, valid until ' . e(fdate($o['valid_until'])) . '.</p>'
                . '<p><a href="' . e($publicUrl) . '" style="display:inline-block;padding:10px 18px;background:#0f766e;color:#fff;border-radius:6px;text-decoration:none">View offer</a></p>';
            $emailed = NotificationService::send('email', $email, 'Offer ' . $o['offer_number'] . ' — ' . $o['project_name'], NotificationService::emailLayout('Your share offer', $html));
        }
        Database::update('offers', ['status' => $o['status'] === 'draft' ? 'sent' : $o['status'], 'sent_at' => now()], 'id = ? AND company_id = ?', [$id, Tenant::id()]);
        $desc = 'Offer ' . $o['offer_number'] . ' sent (' . money($o['total']) . ')' . ($emailed ? ' by email' : '');
        if ($o['customer_id']) {
            ActivityService::system('customer', (int) $o['customer_id'], 'offer_sent', $desc);
        } elseif ($o['lead_id']) {
            ActivityService::system('lead', (int) $o['lead_id'], 'offer_sent', $desc);
            if ($statusId = MasterDataService::idByCode('lead_status', 'offer_sent')) {
                Database::run("UPDATE leads l JOIN master_data s ON s.id = l.status_id SET l.status_id = ? WHERE l.id = ? AND l.company_id = ? AND s.code IN ('new', 'contacted', 'interested', 'follow_up')", [$statusId, $o['lead_id'], Tenant::id()]);
            }
        }
        AuditLogService::log('SEND_OFFER', 'offers', $id, null, ['emailed' => $emailed]);
        return [$emailed, $publicUrl];
    }

    public static function setStatus(int $id, string $status): void
    {
        $o = self::find($id);
        if (!in_array($status, ['accepted', 'rejected', 'sent', 'draft'], true)) {
            throw ValidationException::rule('Invalid status.');
        }
        if (in_array($o['status'], ['converted'], true)) {
            throw ValidationException::rule('Converted offers cannot change status.');
        }
        Database::update('offers', ['status' => $status], 'id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('UPDATE_OFFER_STATUS', 'offers', $id, ['status' => $o['status']], ['status' => $status]);
    }

    public static function delete(int $id): void
    {
        $o = self::find($id);
        if ($o['status'] === 'converted') {
            throw ValidationException::rule('Converted offers cannot be deleted.');
        }
        Database::run('UPDATE offers SET deleted_at = NOW() WHERE id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('DELETE_OFFER', 'offers', $id, $o, null);
    }

    /** Public view: first view of a sent offer flips it to "viewed". */
    public static function markViewed(array $offer): void
    {
        if ($offer['status'] === 'sent') {
            Database::update('offers', ['status' => 'viewed', 'viewed_at' => now()], 'id = ?', [$offer['id']]);
        }
    }

    public static function expireOld(): void
    {
        static $done = false;
        if (!$done) {
            $done = true;
            Database::run("UPDATE offers SET status = 'expired' WHERE company_id = ? AND status IN ('draft', 'sent', 'viewed') AND valid_until < CURDATE() AND deleted_at IS NULL", [Tenant::id()]);
        }
    }

    /** Data for the printable/public offer (company passed explicitly — works without a session). */
    public static function printData(array $offerRow): array
    {
        $cid = (int) $offerRow['company_id'];
        $o = Database::fetch(
            'SELECT o.*, p.project_name, p.project_code, p.location, p.share_unit, sp.name AS package_name, sp.shares_count,
                    COALESCE(c.name, l.name) AS to_name, COALESCE(c.phone, l.phone) AS to_phone, COALESCE(c.email, l.email) AS to_email,
                    COALESCE(c.address, l.address) AS to_address, u.name AS created_by_name
             FROM offers o JOIN projects p ON p.id = o.project_id LEFT JOIN share_packages sp ON sp.id = o.package_id
             LEFT JOIN customers c ON c.id = o.customer_id LEFT JOIN leads l ON l.id = o.lead_id LEFT JOIN users u ON u.id = o.created_by
             WHERE o.id = ? AND o.company_id = ?',
            [$offerRow['id'], $cid]
        );
        $company = Database::fetch('SELECT * FROM companies WHERE id = ?', [$cid]);
        return ['o' => $o, 'company' => $company, 'title' => 'Offer ' . $o['offer_number']];
    }
}
