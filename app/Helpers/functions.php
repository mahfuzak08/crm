<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Money;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;
use App\Services\SettingsService;
use App\Services\Tenant;

/* ---------- config / paths ---------- */

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/** URL path prefix where the app is mounted, e.g. "/crm" (no trailing slash). */
function base_path_prefix(): string
{
    static $prefix = null;
    if ($prefix !== null) {
        return $prefix;
    }
    $configured = (string) config('url', '');
    if ($configured !== '') {
        $prefix = rtrim((string) parse_url($configured, PHP_URL_PATH), '/');
        return $prefix;
    }
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = preg_replace('#/public$#', '', rtrim($dir, '/'));
    return $prefix = $dir === '/' ? '' : (string) $dir;
}

function app_url(): string
{
    $configured = (string) config('url', '');
    if ($configured !== '') {
        return $configured;
    }
    $scheme = App\Core\Request::isHttps() ? 'https' : 'http';
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    return $scheme . '://' . $host . base_path_prefix();
}

function url(string $path = '/', array $query = []): string
{
    $u = base_path_prefix() . '/' . ltrim($path, '/');
    if ($query) {
        $u .= '?' . http_build_query(array_filter($query, static fn($v) => $v !== null && $v !== ''));
    }
    return $u;
}

/** Absolute URL (for emails / share links). */
function full_url(string $path = '/', array $query = []): string
{
    $origin = preg_replace('#^(https?://[^/]+).*$#', '$1', app_url());
    return $origin . url($path, $query);
}

function asset(string $path): string
{
    static $ver = null;
    $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : 1;
    return base_path_prefix() . '/assets/' . ltrim($path, '/') . '?v=' . $v;
}

/** Current URL with some query params replaced (used by pagination/sorting). */
function url_with(array $params): string
{
    $q = array_merge($_GET, $params);
    $q = array_filter($q, static fn($v) => is_string($v) || is_int($v));
    return url(App\Core\Request::path(), $q);
}

/* ---------- output / security ---------- */

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return Csrf::field();
}

function verify_csrf(): void
{
    Csrf::verify();
}

function render(string $view, array $data = [], ?string $layout = 'layouts/app'): never
{
    App\Core\Response::html(View::render($view, $data, $layout));
}

function partial(string $view, array $data = []): string
{
    return View::partial($view, $data);
}

function safe_filename(string $name): string
{
    $name = basename(str_replace('\\', '/', $name));
    $name = preg_replace('/[^\pL\pN._\- ]+/u', '_', $name) ?? 'file';
    $name = trim($name, '. ');
    return mb_substr($name !== '' ? $name : 'file', 0, 120);
}

function intval_if_numeric(string $v): int|string
{
    return ctype_digit($v) ? (int) $v : $v;
}

/* ---------- forms ---------- */

function old(string $key, mixed $default = ''): mixed
{
    $old = Session::getFlash('_old', []);
    return array_key_exists($key, $old) ? $old[$key] : $default;
}

/** Value for a form field: old input first, then record, then default. */
function fv(?array $record, string $key, mixed $default = ''): string
{
    $old = Session::getFlash('_old', []);
    if (array_key_exists($key, $old)) {
        return (string) (is_array($old[$key]) ? '' : $old[$key]);
    }
    return (string) ($record[$key] ?? $default);
}

function errors(): array
{
    return Session::getFlash('_errors', []);
}

function error_for(string $field): string
{
    $err = errors()[$field] ?? null;
    return $err ? '<div class="invalid-feedback d-block">' . e($err) . '</div>' : '';
}

function invalid(string $field): string
{
    return isset(errors()[$field]) ? ' is-invalid' : '';
}

function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}

function checked(mixed $v): string
{
    return !empty($v) ? ' checked' : '';
}

function flash(string $type, string $message): void
{
    Session::flash('_alert', ['type' => $type, 'message' => $message]);
}

/* ---------- auth / tenant ---------- */

function auth(): ?array
{
    return AuthService::user();
}

function auth_id(): ?int
{
    return AuthService::id();
}

function can(string $permission): bool
{
    return AuthService::can($permission);
}

function tenant_id(): int
{
    return Tenant::id();
}

function company(): array
{
    return Tenant::company();
}

function setting(string $key, mixed $default = null): mixed
{
    return SettingsService::get($key, $default);
}

/* ---------- formatting ---------- */

function money(mixed $amount, bool $symbol = true): string
{
    $sym = $symbol ? (string) (company()['currency_symbol'] ?? '') : null;
    return e(Money::format(is_numeric($amount) || is_string($amount) ? (string) $amount : '0', $sym));
}

function is_valid_date(string $d): bool
{
    $dt = DateTime::createFromFormat('!Y-m-d', $d);
    return $dt !== false && $dt->format('Y-m-d') === $d;
}

function fdate(?string $date, bool $withTime = false): string
{
    if (!$date || str_starts_with($date, '0000')) {
        return '';
    }
    $fmt = (string) (company()['date_format'] ?? 'd M Y');
    try {
        return (new DateTime($date))->format($withTime ? $fmt . ' h:i A' : $fmt);
    } catch (Throwable) {
        return '';
    }
}

function today(): string
{
    return date('Y-m-d');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/** Bootstrap badge for a status keyword. */
function status_badge(?string $status, ?string $color = null): string
{
    $status = (string) $status;
    $map = [
        'active' => 'success', 'paid' => 'success', 'confirmed' => 'success', 'accepted' => 'success', 'approved' => 'success', 'converted' => 'success', 'completed' => 'success',
        'inactive' => 'secondary', 'draft' => 'secondary', 'expired' => 'secondary',
        'unpaid' => 'warning', 'partial' => 'info', 'sent' => 'info', 'viewed' => 'primary', 'reserved' => 'info', 'pending' => 'warning', 'ongoing' => 'primary',
        'cancelled' => 'danger', 'rejected' => 'danger', 'overdue' => 'danger', 'lost' => 'danger', 'suspended' => 'danger', 'locked' => 'danger',
    ];
    if ($color && preg_match('/^#[0-9a-f]{6}$/i', $color)) {
        return '<span class="badge" style="background:' . e($color) . '">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
    }
    $cls = $map[strtolower($status)] ?? 'light text-dark';
    return '<span class="badge text-bg-' . $cls . '">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
}

/** Sortable column header link. */
function sort_link(string $key, string $label): string
{
    $cur = $_GET['sort'] ?? '';
    $dir = ($cur === $key && ($_GET['dir'] ?? '') === 'asc') ? 'desc' : 'asc';
    $icon = $cur === $key ? (($_GET['dir'] ?? '') === 'asc' ? ' <i class="bi bi-caret-up-fill"></i>' : ' <i class="bi bi-caret-down-fill"></i>') : '';
    return '<a class="sort-link" href="' . e(url_with(['sort' => $key, 'dir' => $dir, 'page' => 1])) . '">' . e($label) . $icon . '</a>';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $i = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $i .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $i ?: '?';
}

function phone_digits(?string $phone): string
{
    return preg_replace('/\D+/', '', (string) $phone) ?? '';
}

/* ---------- form field builders (labels, old input, errors, escaping) ---------- */

function field_attrs(array $attrs): string
{
    $out = '';
    foreach ($attrs as $k => $v) {
        if ($v === false || $v === null) {
            continue;
        }
        $out .= ' ' . e($k) . ($v === true ? '' : '="' . e($v) . '"');
    }
    return $out;
}

function field_wrap(string $name, string $label, string $control, array $o): string
{
    $req = !empty($o['required']) ? ' <span class="req">*</span>' : '';
    $help = !empty($o['help']) ? '<div class="form-text">' . e($o['help']) . '</div>' : '';
    $lbl = $label !== '' ? '<label class="form-label" for="f_' . e($name) . '">' . e($label) . $req . '</label>' : '';
    return '<div class="' . e($o['col'] ?? 'col-md-6') . '">' . $lbl . $control . $help . error_for($name) . '</div>';
}

/** <input>. $o: type, required, col, help, placeholder, attrs[], value */
function form_input(string $name, string $label, ?array $rec = null, array $o = []): string
{
    $type = $o['type'] ?? 'text';
    $value = $type === 'password' ? '' : ($o['value'] ?? fv($rec, $name, $o['default'] ?? ''));
    $attrs = ['type' => $type, 'name' => $name, 'id' => 'f_' . $name, 'class' => 'form-control' . invalid($name),
        'value' => $value, 'required' => !empty($o['required']), 'placeholder' => $o['placeholder'] ?? null] + ($o['attrs'] ?? []);
    if ($type === 'money') {
        $attrs['type'] = 'text';
        $attrs['inputmode'] = 'decimal';
        $attrs['pattern'] = '[0-9]*\.?[0-9]{0,2}';
    }
    return field_wrap($name, $label, '<input' . field_attrs($attrs) . '>', $o);
}

/** <select>. $options = [value => label]. $o: empty (placeholder text), required, col, attrs[] */
function form_select(string $name, string $label, array $options, ?array $rec = null, array $o = []): string
{
    $cur = $o['value'] ?? fv($rec, $name, $o['default'] ?? '');
    $html = '<select' . field_attrs(['name' => $name, 'id' => 'f_' . $name, 'class' => 'form-select' . invalid($name), 'required' => !empty($o['required'])] + ($o['attrs'] ?? [])) . '>';
    if (array_key_exists('empty', $o)) {
        $html .= '<option value="">' . e($o['empty']) . '</option>';
    }
    foreach ($options as $v => $l) {
        $html .= '<option value="' . e($v) . '"' . selected($v, $cur) . '>' . e($l) . '</option>';
    }
    return field_wrap($name, $label, $html . '</select>', $o);
}

function form_textarea(string $name, string $label, ?array $rec = null, array $o = []): string
{
    $attrs = ['name' => $name, 'id' => 'f_' . $name, 'class' => 'form-control' . invalid($name), 'rows' => $o['rows'] ?? 3,
        'required' => !empty($o['required']), 'placeholder' => $o['placeholder'] ?? null] + ($o['attrs'] ?? []);
    return field_wrap($name, $label, '<textarea' . field_attrs($attrs) . '>' . e($o['value'] ?? fv($rec, $name)) . '</textarea>', $o + ['col' => 'col-12']);
}

function form_check(string $name, string $label, ?array $rec = null, array $o = []): string
{
    $old = App\Core\Session::getFlash('_old', null);
    $on = $old !== null ? !empty($old[$name]) : (bool) ($rec[$name] ?? ($o['default'] ?? false));
    return '<div class="' . e($o['col'] ?? 'col-12') . '"><div class="form-check form-switch"><input type="hidden" name="' . e($name) . '" value="0">'
        . '<input class="form-check-input" type="checkbox" role="switch" value="1" name="' . e($name) . '" id="f_' . e($name) . '"' . ($on ? ' checked' : '') . '>'
        . '<label class="form-check-label" for="f_' . e($name) . '">' . e($label) . '</label></div>'
        . (!empty($o['help']) ? '<div class="form-text">' . e($o['help']) . '</div>' : '') . '</div>';
}

/** Empty-state row for tables. */
function empty_row(int $cols, string $text = 'No records found.'): string
{
    return '<tr><td colspan="' . $cols . '"><div class="empty"><i class="bi bi-inbox"></i>' . e($text) . '</div></td></tr>';
}

/** Small POST button form (delete/cancel/status). */
function post_button(string $action, string $label, array $o = []): string
{
    $confirm = isset($o['confirm']) ? ' data-confirm="' . e($o['confirm']) . '"' : '';
    $reason = !empty($o['reason']) ? ' data-reason' : '';
    $hidden = '';
    foreach ($o['fields'] ?? [] as $k => $v) {
        $hidden .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    return '<form method="post" action="' . e(url($action)) . '" class="d-inline"' . $confirm . $reason . '>' . csrf_field() . $hidden
        . '<button type="submit" class="' . e($o['class'] ?? 'btn btn-sm btn-outline-danger') . '"' . (isset($o['title']) ? ' title="' . e($o['title']) . '"' : '') . '>' . ($o['html'] ?? e($label)) . '</button></form>';
}

/** Money with an explicit company's currency symbol (print/public pages). */
function money_c(mixed $amount, ?array $company): string
{
    return e(App\Core\Money::format(is_numeric($amount) || is_string($amount) ? (string) $amount : '0', (string) ($company['currency_symbol'] ?? '')));
}

/** Amount in words (English, Bangladeshi lakh/crore grouping) for receipts. */
function amount_in_words(string $amount, string $currency = 'Taka'): string
{
    $cents = App\Core\Money::toCents($amount);
    $taka = intdiv(abs($cents), 100);
    $paisa = abs($cents) % 100;
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    $two = static fn(int $n): string => $n < 20 ? $ones[$n] : trim($tens[intdiv($n, 10)] . ' ' . $ones[$n % 10]);
    $three = static fn(int $n): string => trim(($n >= 100 ? $ones[intdiv($n, 100)] . ' Hundred ' : '') . $two($n % 100));
    $words = static function (int $n) use ($two, $three, &$words): string {
        if ($n === 0) {
            return 'Zero';
        }
        $parts = [];
        if ($n >= 10000000) { $parts[] = $words(intdiv($n, 10000000)) . ' Crore'; $n %= 10000000; }
        if ($n >= 100000) { $parts[] = $two(intdiv($n, 100000)) . ' Lakh'; $n %= 100000; }
        if ($n >= 1000) { $parts[] = $two(intdiv($n, 1000)) . ' Thousand'; $n %= 1000; }
        if ($n > 0) { $parts[] = $three($n); }
        return implode(' ', $parts);
    };
    $out = $words($taka) . ' ' . $currency;
    if ($paisa > 0) {
        $out .= ' and ' . $two($paisa) . ' Paisa';
    }
    return $out . ' Only';
}

/** setting() that never throws (public pages without a company session). */
function setting_safe(string $key, mixed $default = null): mixed
{
    try {
        return isset($_SESSION['cid']) ? setting($key, $default) : $default;
    } catch (Throwable) {
        return $default;
    }
}

/** Compact amount for KPI cards: ৳ 3.85 Cr / ৳ 12.50 L (full value in a title tooltip). */
function money_short(mixed $amount): string
{
    $cents = App\Core\Money::toCents((string) $amount);
    $abs = abs($cents);
    $sym = (string) (company()['currency_symbol'] ?? '');
    $fmt = match (true) {
        $abs >= 1_000_000_000 => number_format($cents / 1_000_000_000, 2) . ' Cr',
        $abs >= 10_000_000 => number_format($cents / 10_000_000, 2) . ' L',
        default => number_format($cents / 100, 2),
    };
    return '<span title="' . e(money($amount)) . '">' . e(trim($sym . ' ' . $fmt)) . '</span>';
}
