<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Services\CustomerService;
use App\Services\MasterDataService;
use App\Services\ProjectService;
use App\Services\ReportService;
use App\Services\Tenant;
use App\Services\UserService;

final class ReportController extends Controller
{
    private const FILTERS = ['q', 'from', 'to', 'company', 'project', 'customer', 'user', 'method', 'category', 'status', 'lead_status', 'source', 'department', 'emp_status', 'only_due'];

    public function index(): never
    {
        $this->authorize('reports.view');
        render('reports/index', ['title' => 'Reports', 'breadcrumbs' => ['Reports' => null], 'reports' => ReportService::REPORTS]);
    }

    public function show(string $type): never
    {
        $this->authorize('reports.view');
        if (isset(ReportService::REQUIRES[$type])) {
            $this->authorize(ReportService::REQUIRES[$type]);
        }
        if ($type === 'financial') {
            $this->financial();
        }
        if (!isset(ReportService::REPORTS[$type])) {
            throw new HttpException(404);
        }
        $f = Request::filters(self::FILTERS);
        $r = ReportService::build($type, $f);
        [$title, $desc] = ReportService::REPORTS[$type];

        $mode = Request::str('export');
        if ($mode === 'csv') {
            $this->authorize('reports.export');
            AuditLogService::log('EXPORT_REPORT', 'reports', null, null, ['report' => $type, 'filters' => array_filter($f)]);
            $cols = $r['columns'];
            $rows = (static function () use ($r, $cols) {
                foreach ($r['query']->cursor() as $row) {
                    yield array_map(static fn($k) => $row[$k] ?? '', array_keys($cols));
                }
            })();
            Response::csv($type . '-report-' . date('Ymd') . '.csv', array_column($cols, 0), $rows);
        }
        $data = [
            'title' => $title, 'desc' => $desc, 'type' => $type, 'f' => $f, 'r' => $r,
            'breadcrumbs' => ['Reports' => '/reports', $title => null],
            'options' => $this->options($r['filters'] ?? []),
        ];
        if ($mode === 'print') {
            $data['rows'] = $r['query']->get(5000);
            render('reports/print', $data + ['company' => Tenant::company(), 'docTitle' => $title], 'layouts/print');
        }
        $data['p'] = $r['query']->paginate($this->page(), 25);
        $this->view('reports/show', $data, 'reports/_table');
    }

    private function financial(): never
    {
        $f = Request::filters(['from', 'to', 'company']);
        $d = ReportService::financial($f);
        $data = ['title' => 'Financial Summary', 'breadcrumbs' => ['Reports' => '/reports', 'Financial Summary' => null], 'f' => $f, 'd' => $d,
            'companies' => AuthService::isSuperAdmin() ? Tenant::allowedCompanies() : []];
        if (Request::str('export') === 'print') {
            render('reports/financial_print', $data + ['company' => Tenant::company(), 'docTitle' => 'Financial Summary'], 'layouts/print');
        }
        if (Request::str('export') === 'csv') {
            $this->authorize('reports.export');
            $rows = array_map(static fn($m) => [$m['ym'], $m['sales'], $m['collection'], $m['expenses'], $m['salaries'], $m['net']], $d['monthly']);
            Response::csv('financial-summary-' . date('Ymd') . '.csv', ['Month', 'Sales value', 'Collection', 'Expenses', 'Salaries', 'Net cash flow'], $rows);
        }
        render('reports/financial', $data);
    }

    /** Dropdown data for the filters a report uses. */
    private function options(array $filters): array
    {
        $o = [];
        if (AuthService::isSuperAdmin()) {
            $o['companies'] = Tenant::allowedCompanies();
        }
        if (in_array('project', $filters, true)) $o['projects'] = ProjectService::options();
        if (in_array('customer', $filters, true)) $o['customers'] = CustomerService::options();
        if (in_array('user', $filters, true)) $o['users'] = UserService::options();
        if (in_array('method', $filters, true)) $o['methods'] = MasterDataService::options('payment_method', false);
        if (in_array('category', $filters, true)) $o['categories'] = MasterDataService::options('expense_category', false);
        if (in_array('lead_status', $filters, true)) $o['lead_statuses'] = MasterDataService::options('lead_status', false);
        if (in_array('source', $filters, true)) $o['sources'] = MasterDataService::options('lead_source', false);
        if (in_array('department', $filters, true)) $o['departments'] = MasterDataService::options('department', false);
        return $o;
    }
}
