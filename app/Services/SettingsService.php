<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/** Per-company key/value settings, loaded once per request. */
final class SettingsService
{
    /** Defaults for business settings (overridable per company). */
    public const DEFAULTS = [
        'invoice.default_due_days'   => '30',
        'invoice.default_tax_percent'=> '0',
        'invoice.terms'              => 'Payment is due within the due date. Shares are transferred only after full payment.',
        'invoice.template'           => 'classic',
        'offer.valid_days'           => '15',
        'offer.terms'                => 'This offer is valid until the date mentioned above and is subject to share availability.',
        'sales.max_discount_percent' => '10',
        'sales.allow_fee_override'   => '0',
        'sales.auto_invoice'         => '1',
        'agreement.terms'            => "1. The purchaser agrees to pay the full price as per the payment schedule.\n2. Share ownership is transferred after full payment and registration.\n3. Shares may be transferred only with the company's written approval.\n4. All disputes are subject to the jurisdiction of the courts of Bangladesh.",
        'expense.require_approval'   => '0',
        'mail.enabled'               => '0',
    ];

    private static ?array $cache = null;
    private static ?int $cacheCompany = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        if (array_key_exists($key, $all)) {
            return $all[$key];
        }
        return $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function all(): array
    {
        $cid = (int) ($_SESSION['cid'] ?? 0);
        if (self::$cache === null || self::$cacheCompany !== $cid) {
            self::$cacheCompany = $cid;
            self::$cache = self::DEFAULTS;
            if ($cid > 0) {
                foreach (Database::pairs('SELECT skey, svalue FROM settings WHERE company_id = ?', [$cid]) as $k => $v) {
                    self::$cache[$k] = $v;
                }
            }
        }
        return self::$cache;
    }

    /** Persist values (keys must be whitelisted by the caller). */
    public static function set(array $values, ?int $companyId = null): void
    {
        $cid = $companyId ?? Tenant::id();
        foreach ($values as $k => $v) {
            Database::run(
                'INSERT INTO settings (company_id, skey, svalue) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)',
                [$cid, $k, $v === null ? null : (string) $v]
            );
        }
        self::reset();
    }

    public static function reset(): void
    {
        self::$cache = null;
    }
}
