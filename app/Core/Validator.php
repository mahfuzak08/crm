<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Server-side validation. Returns ONLY validated keys (mass-assignment safe).
 *
 *   $data = Validator::make($_POST, [
 *       'name'   => 'required|max:150',
 *       'email'  => 'nullable|email',
 *       'amount' => 'required|money|gt:0',
 *       'status' => ['required', 'in:a,b', fn($v) => ok($v) ? null : 'Invalid status'],
 *   ]);
 *
 * Empty optional values become null. Closures return an error string or null.
 */
final class Validator
{
    public static function make(array $input, array $rules, array $labels = []): array
    {
        $errors = [];
        $out = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $label = $labels[$field] ?? ucfirst(str_replace(['_id', '_'], ['', ' '], $field));
            $value = $input[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }

            $isBool = in_array('bool', $fieldRules, true);
            if ($isBool) {
                $out[$field] = !empty($value) && $value !== '0' ? 1 : 0;
                continue;
            }

            $empty = $value === null || $value === '' || $value === [];
            if ($empty) {
                if (in_array('required', $fieldRules, true)) {
                    $errors[$field] = $label . ' is required.';
                } else {
                    $out[$field] = null;
                }
                continue;
            }
            if (is_array($value) && !in_array('array', $fieldRules, true)) {
                $errors[$field] = $label . ' is invalid.';
                continue;
            }

            foreach ($fieldRules as $rule) {
                if ($rule === 'required' || $rule === 'nullable' || $rule === 'array') {
                    continue;
                }
                $error = $rule instanceof \Closure
                    ? $rule($value, $input)
                    : self::check((string) $rule, $value, $label, $input);
                if ($error !== null) {
                    $errors[$field] = $error;
                    break;
                }
            }
            if (!isset($errors[$field])) {
                $out[$field] = self::cast($fieldRules, $value);
            }
        }

        if ($errors) {
            throw new ValidationException($errors);
        }
        return $out;
    }

    private static function check(string $rule, mixed $value, string $label, array $input): ?string
    {
        [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
        $str = is_scalar($value) ? (string) $value : '';

        return match ($name) {
            'max'      => mb_strlen($str) > (int) $arg ? "$label may not exceed $arg characters." : null,
            'min'      => mb_strlen($str) < (int) $arg ? "$label must be at least $arg characters." : null,
            'email'    => filter_var($str, FILTER_VALIDATE_EMAIL) && mb_strlen($str) <= 150 ? null : "$label must be a valid email address.",
            'phone'    => preg_match('/^\+?[0-9][0-9\-\s()]{5,19}$/', $str) ? null : "$label must be a valid phone number.",
            'integer'  => preg_match('/^-?\d{1,10}$/', $str) ? null : "$label must be a whole number.",
            'id'       => preg_match('/^[1-9]\d{0,9}$/', $str) ? null : "$label is invalid.",
            'numeric'  => is_numeric($str) ? null : "$label must be a number.",
            'decimal'  => preg_match('/^\d{1,13}(\.\d{1,4})?$/', $str) ? null : "$label must be a valid number.",
            'money'    => Money::isValid($str) ? null : "$label must be a valid amount (max 2 decimals).",
            'date'     => is_valid_date($str) ? null : "$label must be a valid date.",
            'month'    => preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $str) ? null : "$label must be a valid month.",
            'in'       => in_array($str, explode(',', (string) $arg), true) ? null : "$label is invalid.",
            'gt'       => is_numeric($str) && (float) $str > (float) $arg ? null : "$label must be greater than $arg.",
            'gte'      => is_numeric($str) && (float) $str >= (float) $arg ? null : "$label must be at least $arg.",
            'lte'      => is_numeric($str) && (float) $str <= (float) $arg ? null : "$label may not be greater than $arg.",
            'url'      => filter_var($str, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $str) ? null : "$label must be a valid http(s) URL.",
            'username' => preg_match('/^(?=.*[A-Za-z])[a-zA-Z0-9_.\-]{3,50}$/', $str) ? null : "$label must contain a letter and may contain letters, numbers, dot, dash, underscore (3-50).",
            'code'     => preg_match('/^[A-Za-z0-9_\-]{1,30}$/', $str) ? null : "$label may contain letters, numbers, dash, underscore.",
            'color'    => preg_match('/^#[0-9a-fA-F]{6}$/', $str) ? null : "$label must be a hex color.",
            'password' => (mb_strlen($str) >= 8 && preg_match('/[A-Za-z]/', $str) && preg_match('/\d/', $str))
                ? null : "$label must be at least 8 characters and contain letters and numbers.",
            'same'     => isset($input[$arg]) && hash_equals((string) $input[$arg], $str) ? null : "$label confirmation does not match.",
            'after_or_equal' => (isset($input[$arg]) && is_valid_date((string) $input[$arg]) && $str < $input[$arg])
                ? "$label must be on or after " . str_replace('_', ' ', $arg) . '.' : null,
            default    => throw new \InvalidArgumentException('Unknown validation rule: ' . $name),
        };
    }

    private static function cast(array $rules, mixed $value): mixed
    {
        if (in_array('integer', $rules, true) || in_array('id', $rules, true)) {
            return (int) $value;
        }
        if (in_array('money', $rules, true)) {
            return Money::normalize((string) $value);
        }
        if (in_array('email', $rules, true)) {
            return strtolower((string) $value);
        }
        return $value;
    }
}
