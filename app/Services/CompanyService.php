<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Validator;

final class CompanyService
{
    public const FIELDS = ['name', 'code', 'address', 'phone', 'email', 'website', 'tax_info', 'currency', 'currency_symbol',
        'timezone', 'date_format', 'invoice_footer', 'receipt_footer', 'theme_color'];

    public const DATE_FORMATS = ['d M Y' => '07 Oct 2026', 'd/m/Y' => '07/10/2026', 'd-m-Y' => '07-10-2026', 'Y-m-d' => '2026-10-07', 'M d, Y' => 'Oct 07, 2026'];

    public static function validate(array $input, ?int $id = null): array
    {
        $data = Validator::make($input, [
            'name'            => 'required|max:150',
            'code'            => ['nullable', 'code', static fn($v) => Database::value('SELECT 1 FROM companies WHERE code = ? AND id <> ?', [$v, $id ?? 0]) ? 'Code already used.' : null],
            'address'         => 'nullable|max:1000',
            'phone'           => 'nullable|phone',
            'email'           => 'nullable|email',
            'website'         => 'nullable|url|max:200',
            'tax_info'        => 'nullable|max:255',
            'currency'        => 'nullable|code|max:10',
            'currency_symbol' => ['nullable', 'max:10', static fn($v) => preg_match('/[<>&"\'`]/', $v) ? 'Invalid characters.' : null],
            'timezone'        => ['nullable', static fn($v) => in_array($v, \DateTimeZone::listIdentifiers(), true) ? null : 'Invalid timezone.'],
            'date_format'     => ['nullable', 'in:' . implode(',', array_keys(self::DATE_FORMATS))],
            'invoice_footer'  => 'nullable|max:2000',
            'receipt_footer'  => 'nullable|max:2000',
            'theme_color'     => 'nullable|color',
        ]);
        $data['currency'] ??= 'BDT';
        $data['currency_symbol'] ??= '৳';
        $data['timezone'] ??= 'Asia/Dhaka';
        $data['date_format'] ??= 'd M Y';
        $data['theme_color'] ??= '#0f766e';
        return $data;
    }

    /** Create a company with all default configuration. */
    public static function create(array $data): int
    {
        return Database::transaction(static function () use ($data) {
            $id = Database::insert('companies', $data);
            self::seedDefaults($id);
            AuditLogService::log('CREATE_COMPANY', 'companies', $id, null, $data, $id);
            return $id;
        });
    }

    public static function seedDefaults(int $companyId): void
    {
        MasterDataService::seed($companyId);
        NumberGeneratorService::ensure($companyId);
        SalaryService::seedComponents($companyId);
    }
}
