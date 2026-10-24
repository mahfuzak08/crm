<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\CustomerService;
use App\Services\MasterDataService;
use App\Services\OfferService;
use App\Services\ProjectService;
use App\Services\SaleService;
use App\Services\SettingsService;
use App\Services\Tenant;
use App\Services\UserService;

final class SaleController extends Controller
{
    public function index(): never
    {
        $this->authorize('sales.view');
        $f = Request::filters(['q', 'status', 'project', 'customer', 'sales_person', 'from', 'to', 'sort', 'dir']);
        if (Request::str('export') === 'csv') {
            $this->authorize('reports.export');
            $rows = (static function () use ($f) {
                foreach (SaleService::query($f)->cursor() as $r) {
                    yield [$r['sale_number'], $r['sale_date'], $r['customer_name'], $r['project_name'], $r['package_name'], $r['shares'], $r['grand_total'], $r['paid_amount'], $r['due_amount'], $r['status'], $r['sales_person']];
                }
            })();
            Response::csv('share-sales-' . date('Ymd') . '.csv', ['Sale', 'Date', 'Customer', 'Project', 'Package', 'Shares', 'Total', 'Paid', 'Due', 'Status', 'Sales person'], $rows);
        }
        $q = SaleService::query($f);
        $this->view('sales/index', [
            'title' => 'Share Sales', 'breadcrumbs' => ['Sales' => null, 'Share Sales' => null], 'f' => $f,
            'p' => $q->paginate($this->page()),
            'totals' => SaleService::query($f)->where("s.status = 'confirmed'")->aggregate(['shares' => 'COALESCE(SUM(s.shares),0)', 'total' => 'COALESCE(SUM(s.grand_total),0)', 'due' => 'COALESCE(SUM(s.due_amount),0)']),
            'projects' => ProjectService::options(), 'users' => UserService::options(),
        ], 'sales/_list');
    }

    public function create(): never
    {
        $this->authorize('sales.create');
        $rec = ['sale_date' => today(), 'quantity' => 1, 'status' => 'confirmed', 'sales_person_id' => auth_id(), 'idempotency_key' => bin2hex(random_bytes(16))];
        if ($pid = Request::int('project')) {
            $rec['project_id'] = $pid;
        }
        if ($cid = Request::int('customer')) {
            CustomerService::find($cid);
            $rec['customer_id'] = $cid;
        }
        $offer = null;
        if ($oid = Request::int('offer')) {
            $offer = OfferService::find($oid);
            $rec = array_merge($rec, array_intersect_key($offer, array_flip(['customer_id', 'project_id', 'package_id', 'quantity', 'discount', 'other_fee', 'registration_fee', 'development_fee'])));
            $rec['offer_id'] = $oid;
        }
        render('sales/form', [
            'title' => 'New Share Sale', 'breadcrumbs' => ['Share Sales' => '/sales', 'New' => null],
            'rec' => $rec, 'offer' => $offer,
            'projects' => ProjectService::options(),
            'customers' => CustomerService::options(isset($rec['customer_id']) ? (int) $rec['customer_id'] : null),
            'users' => UserService::options(), 'methods' => MasterDataService::options('payment_method'),
            'feeOverride' => SettingsService::get('sales.allow_fee_override') === '1',
        ]);
    }

    public function store(): never
    {
        $this->authorize('sales.create');
        $id = SaleService::create(Request::post());
        $this->success('Share sale saved.', '/sales/' . $id);
    }

    public function show(int $id): never
    {
        $this->authorize('sales.view');
        $s = SaleService::find($id);
        render('sales/show', [
            'title' => 'Sale ' . $s['sale_number'], 'breadcrumbs' => ['Share Sales' => '/sales', $s['sale_number'] => null], 's' => $s,
            'payments' => $s['invoice_id'] ? Database::fetchAll('SELECT p.*, m.name AS method FROM payments p JOIN master_data m ON m.id = p.payment_method_id WHERE p.invoice_id = ? AND p.company_id = ? ORDER BY p.id', [$s['invoice_id'], Tenant::id()]) : [],
        ]);
    }

    public function confirm(int $id): never
    {
        $this->authorize('sales.create');
        SaleService::confirm($id);
        $this->success('Sale confirmed and invoice created.', '/sales/' . $id);
    }

    public function cancel(int $id): never
    {
        $this->authorize('sales.cancel');
        SaleService::cancel($id, Request::str('reason') ?: null);
        $this->success('Sale cancelled. Shares released back to inventory.', '/sales/' . $id);
    }

    public function agreement(int $id): never
    {
        $this->authorize('sales.view');
        $s = SaleService::find($id);
        render('sales/agreement', ['s' => $s, 'company' => Tenant::company(), 'title' => 'Agreement ' . $s['sale_number'], 'docTitle' => 'Share Purchase Agreement',
            'terms' => SettingsService::get('agreement.terms')], 'layouts/print');
    }
}
