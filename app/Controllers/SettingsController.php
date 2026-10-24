<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\ValidationException;
use App\Core\Validator;
use App\Services\AuditLogService;
use App\Services\CompanyService;
use App\Services\FileUploadService;
use App\Services\NumberGeneratorService;
use App\Services\SettingsService;
use App\Services\Tenant;

final class SettingsController extends Controller
{
    public function company(): never
    {
        $this->authorize('settings.view');
        render('settings/company', [
            'title' => 'Company Setup', 'breadcrumbs' => ['Settings' => null, 'Company' => null],
            'company' => Tenant::company(), 'dateFormats' => CompanyService::DATE_FORMATS,
        ]);
    }

    public function saveCompany(): never
    {
        $this->authorize('settings.edit');
        $cid = Tenant::id();
        $old = Tenant::company();
        $data = CompanyService::validate(Request::post(), $cid);

        if ($file = Request::file('logo')) {
            $data['logo'] = FileUploadService::store($file, ['image'], 'logo', 2)['stored_name'];
        } elseif (Request::str('remove_logo') === '1') {
            $data['logo'] = null;
        }
        Database::update('companies', $data, 'id = ?', [$cid]);
        if (array_key_exists('logo', $data) && $old['logo'] && $old['logo'] !== $data['logo']) {
            FileUploadService::delete($old['logo']);
        }
        AuditLogService::log('CHANGE_SETTINGS', 'company', $cid, $old, $data);
        $this->success('Company settings saved.', '/settings/company');
    }

    public function invoice(): never
    {
        $this->authorize('settings.view');
        $seqs = Database::fetchAll('SELECT * FROM number_sequences WHERE company_id = ? ORDER BY FIELD(seq_key, ' . Database::placeholders(array_keys(NumberGeneratorService::SEQUENCES)) . ')',
            [Tenant::id(), ...array_keys(NumberGeneratorService::SEQUENCES)]);
        render('settings/invoice', [
            'title' => 'Invoice & Numbering', 'breadcrumbs' => ['Settings' => null, 'Invoice & Numbering' => null],
            'seqs' => $seqs, 's' => SettingsService::all(),
        ]);
    }

    public function saveInvoice(): never
    {
        $this->authorize('settings.edit');
        $in = Request::post();
        $data = Validator::make($in, [
            'invoice_default_due_days'    => 'required|integer|gte:0|lte:3650',
            'invoice_default_tax_percent' => 'required|decimal|lte:100',
            'invoice_template'            => 'required|in:classic,modern',
            'invoice_terms'               => 'nullable|max:3000',
        ], ['invoice_default_due_days' => 'Default due days', 'invoice_default_tax_percent' => 'Default tax %']);

        // Number sequences
        $seqIn = is_array($in['seq'] ?? null) ? $in['seq'] : [];
        $seqData = [];
        foreach (array_keys(NumberGeneratorService::SEQUENCES) as $key) {
            $row = is_array($seqIn[$key] ?? null) ? $seqIn[$key] : [];
            $v = Validator::make($row, [
                'prefix' => ['required', 'max:20', static fn($p) => preg_match('/^[A-Za-z0-9\/_]{1,20}$/', $p) ? null : 'Prefix may contain letters, numbers, / and _.'],
                'next_number' => 'required|integer|gte:1|lte:999999999',
                'number_length' => 'required|integer|gte:1|lte:12',
                'yearly_reset' => 'bool',
            ], ['next_number' => NumberGeneratorService::SEQUENCES[$key][0] . ' next number', 'prefix' => NumberGeneratorService::SEQUENCES[$key][0] . ' prefix']);
            $seqData[$key] = $v;
        }

        Database::transaction(static function () use ($data, $seqData) {
            $old = SettingsService::all();
            SettingsService::set([
                'invoice.default_due_days' => $data['invoice_default_due_days'],
                'invoice.default_tax_percent' => $data['invoice_default_tax_percent'],
                'invoice.template' => $data['invoice_template'],
                'invoice.terms' => $data['invoice_terms'],
            ]);
            foreach ($seqData as $key => $v) {
                Database::update('number_sequences', $v, 'company_id = ? AND seq_key = ?', [Tenant::id(), $key]);
            }
            AuditLogService::log('CHANGE_SETTINGS', 'invoice_settings', null, array_intersect_key($old, array_flip(['invoice.default_due_days', 'invoice.default_tax_percent', 'invoice.template'])), $data + ['sequences' => json_encode($seqData)]);
        });
        $this->success('Invoice & numbering settings saved.', '/settings/invoice');
    }

    public function sales(): never
    {
        $this->authorize('settings.view');
        render('settings/sales', ['title' => 'Sales, Offers & Expenses', 'breadcrumbs' => ['Settings' => null, 'Sales & Offers' => null], 's' => SettingsService::all()]);
    }

    public function saveSales(): never
    {
        $this->authorize('settings.edit');
        $d = Validator::make(Request::post(), [
            'sales_max_discount_percent' => 'required|decimal|lte:100',
            'sales_allow_fee_override'   => 'bool',
            'sales_auto_invoice'         => 'bool',
            'offer_valid_days'           => 'required|integer|gte:1|lte:365',
            'offer_terms'                => 'nullable|max:3000',
            'agreement_terms'            => 'nullable|max:10000',
            'expense_require_approval'   => 'bool',
        ], ['sales_max_discount_percent' => 'Max discount %', 'offer_valid_days' => 'Offer validity']);
        $map = [];
        foreach ($d as $k => $v) {
            $map[preg_replace('/_/', '.', $k, 1)] = $v;
        }
        $old = array_intersect_key(SettingsService::all(), $map);
        SettingsService::set($map);
        AuditLogService::log('CHANGE_SETTINGS', 'sales_settings', null, $old, $map);
        $this->success('Settings saved.', '/settings/sales');
    }
}
