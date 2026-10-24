<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Money;
use App\Core\ValidationException;

/**
 * Server-side price calculation for share offers and sales.
 * Browser-supplied totals are NEVER used; only raw inputs (quantity, discount,
 * other fee and — if allowed by settings — fees) are accepted.
 */
final class PricingService
{
    /**
     * @param array $in validated: project_id, package_id|null, quantity, discount, other_fee, registration_fee, development_fee
     * @return array{package:?array, shares:int, unit_price:string, subtotal:string, discount:string,
     *               registration_fee:string, development_fee:string, other_fee:string, total:string}
     */
    public static function calculate(array $in): array
    {
        $project = Database::fetch('SELECT * FROM projects WHERE id = ? AND company_id = ? AND deleted_at IS NULL', [(int) $in['project_id'], Tenant::id()]);
        if (!$project) {
            throw new ValidationException(['project_id' => 'Please select a valid project.']);
        }
        $qty = (int) $in['quantity'];
        if ($qty < 1 || $qty > 1_000_000) {
            throw new ValidationException(['quantity' => 'Quantity must be at least 1.']);
        }

        $package = null;
        if (!empty($in['package_id'])) {
            $package = Database::fetch(
                "SELECT * FROM share_packages WHERE id = ? AND project_id = ? AND company_id = ? AND status = 'active' AND deleted_at IS NULL",
                [(int) $in['package_id'], (int) $project['id'], Tenant::id()]
            );
            if (!$package) {
                throw new ValidationException(['package_id' => 'Selected package is not available for this project.']);
            }
        }

        $shares = $package ? $qty * (int) $package['shares_count'] : $qty;
        $unit = Money::toCents($package ? $package['price'] : $project['share_price']);
        $subtotal = Money::times($unit, $qty);

        if (SettingsService::get('sales.allow_fee_override') === '1') {
            $reg = Money::toCents($in['registration_fee'] ?? '0');
            $dev = Money::toCents($in['development_fee'] ?? '0');
        } else {
            $reg = Money::times(Money::toCents($project['registration_fee']), $shares);
            $dev = Money::times(Money::toCents($project['development_fee']), $shares);
        }
        $other = Money::toCents($in['other_fee'] ?? '0');
        $discount = Money::toCents($in['discount'] ?? '0');

        if ($discount < 0 || $reg < 0 || $dev < 0 || $other < 0) {
            throw ValidationException::rule('Amounts cannot be negative.');
        }
        if ($discount > $subtotal) {
            throw new ValidationException(['discount' => 'Discount cannot exceed the share subtotal.']);
        }
        $maxPct = (string) SettingsService::get('sales.max_discount_percent', '100');
        $maxPct = preg_match('/^\d{1,3}(\.\d{1,2})?$/', $maxPct) ? $maxPct : '100';
        if ($discount > Money::percent($subtotal, $maxPct) && !AuthService::can('sales.discount')) {
            throw new ValidationException(['discount' => "Discount may not exceed {$maxPct}% of the subtotal without approval."]);
        }

        $total = $subtotal - $discount + $reg + $dev + $other;
        Money::assertStorable($subtotal, 'quantity');
        Money::assertStorable($total, 'quantity');
        return [
            'project' => $project,
            'package' => $package,
            'shares' => $shares,
            'unit_price' => Money::fromCents($unit),
            'subtotal' => Money::fromCents($subtotal),
            'discount' => Money::fromCents($discount),
            'registration_fee' => Money::fromCents($reg),
            'development_fee' => Money::fromCents($dev),
            'other_fee' => Money::fromCents($other),
            'total' => Money::fromCents($total),
        ];
    }
}
