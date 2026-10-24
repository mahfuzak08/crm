<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Money arithmetic using integer minor units (paisa/cents) — never floats.
 * DB stores DECIMAL(15,2); PHP converts "1234.56" <-> 123456.
 */
final class Money
{
    public static function isValid(string $v): bool
    {
        return (bool) preg_match('/^\d{1,13}(\.\d{1,2})?$/', str_replace(',', '', $v));
    }

    /** "1,234.5" -> "1234.50" */
    public static function normalize(string $v): string
    {
        return self::fromCents(self::toCents($v));
    }

    public static function toCents(string|int|null $v): int
    {
        if ($v === null || $v === '') {
            return 0;
        }
        if (is_int($v)) {
            return $v * 100;
        }
        $v = str_replace(',', '', trim($v));
        if (!preg_match('/^(-)?(\d+)(?:\.(\d{1,2}))?$/', $v, $m)) {
            throw new \InvalidArgumentException('Invalid money value');
        }
        $cents = (int) $m[2] * 100 + (int) str_pad($m[3] ?? '0', 2, '0');
        return $m[1] === '-' ? -$cents : $cents;
    }

    public static function fromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        return $sign . intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Multiply cents by an integer quantity. */
    public static function times(int $cents, int $qty): int
    {
        if ($qty !== 0 && abs($cents) > intdiv(self::MAX, abs($qty))) {
            throw \App\Core\ValidationException::rule('Amount is too large.');
        }
        return $cents * $qty;
    }

    /** Largest value storable in DECIMAL(15,2), in cents. */
    public const MAX = 999_999_999_999_99;

    public static function assertStorable(int $cents, string $field = '_'): void
    {
        if (abs($cents) > self::MAX) {
            throw new ValidationException([$field => 'Amount is too large.']);
        }
    }

    /** Percentage of cents (basis points to avoid floats): pct "12.5" -> rounded half-up. */
    public static function percent(int $cents, string $pct): int
    {
        if (!preg_match('/^(\d{1,3})(?:\.(\d{1,2}))?$/', $pct, $m)) {
            throw new \InvalidArgumentException('Invalid percent');
        }
        $bp = (int) $m[1] * 100 + (int) str_pad($m[2] ?? '0', 2, '0'); // basis points
        return intdiv($cents * $bp + 5000, 10000);
    }

    /** Human format: 1,234,567.00 */
    public static function format(string|int|float|null $amount, ?string $symbol = null): string
    {
        $cents = is_int($amount) ? $amount * 100 : self::toCents(is_float($amount) ? number_format($amount, 2, '.', '') : (string) ($amount ?? '0'));
        $neg = $cents < 0;
        $s = number_format(abs($cents) / 100, 2); // display only
        return ($neg ? '-' : '') . ($symbol !== null && $symbol !== '' ? $symbol . ' ' : '') . $s;
    }
}
