<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\CustomerService;
use App\Services\InvoiceService;
use App\Services\ProjectService;
use App\Services\SettingsService;
use App\Services\Tenant;

final class InvoiceController extends Controller
{
    public function index(): never
    {
        $this->authorize('invoices.view');
        $f = Request::filters(['q', 'status', 'customer', 'project', 'from', 'to', 'sort', 'dir']);
        if (Request::str('export') === 'csv') {
            $this->authorize('reports.export');
            $rows = (static function () use ($f) {
                foreach (InvoiceService::query($f)->cursor() as $r) {
                    yield [$r['invoice_number'], $r['invoice_date'], $r['due_date'], $r['customer_name'], $r['project_name'], $r['grand_total'], $r['paid_amount'], $r['due_amount'], $r['status']];
                }
            })();
            Response::csv('invoices-' . date('Ymd') . '.csv', ['Invoice', 'Date', 'Due date', 'Customer', 'Project', 'Total', 'Paid', 'Due', 'Status'], $rows);
        }
        $this->view('invoices/index', [
            'title' => 'Invoices', 'breadcrumbs' => ['Sales' => null, 'Invoices' => null], 'f' => $f,
            'p' => InvoiceService::query($f)->paginate($this->page()),
            'totals' => InvoiceService::query($f)->where("i.status NOT IN ('cancelled', 'draft')")->aggregate(['total' => 'COALESCE(SUM(i.grand_total),0)', 'paid' => 'COALESCE(SUM(i.paid_amount),0)', 'due' => 'COALESCE(SUM(i.due_amount),0)']),
            'projects' => ProjectService::options(),
        ], 'invoices/_list');
    }

    public function create(): never
    {
        $this->authorize('invoices.create');
        $rec = ['invoice_date' => today(), 'tax_percent' => SettingsService::get('invoice.default_tax_percent', '0'), 'terms' => SettingsService::get('invoice.terms'), 'discount' => '0.00', 'other_charge' => '0.00'];
        if ($cid = Request::int('customer')) {
            CustomerService::find($cid);
            $rec['customer_id'] = $cid;
        }
        $this->form(null, $rec);
    }

    public function store(): never
    {
        $this->authorize('invoices.create');
        $id = InvoiceService::create(Request::post(), Request::str('save_as') === 'draft');
        $this->success('Invoice created.', '/invoices/' . $id);
    }

    public function show(int $id): never
    {
        $this->authorize('invoices.view');
        InvoiceService::refreshOverdue();
        $inv = InvoiceService::find($id);
        render('invoices/show', [
            'title' => 'Invoice ' . $inv['invoice_number'], 'breadcrumbs' => ['Invoices' => '/invoices', $inv['invoice_number'] => null], 'inv' => $inv,
            'payments' => can('payments.view') ? Database::fetchAll('SELECT p.*, m.name AS method, u.name AS received_by_name FROM payments p JOIN master_data m ON m.id = p.payment_method_id LEFT JOIN users u ON u.id = p.received_by WHERE p.invoice_id = ? AND p.company_id = ? ORDER BY p.id', [$id, Tenant::id()]) : [],
        ]);
    }

    public function edit(int $id): never
    {
        $this->authorize('invoices.edit');
        $inv = InvoiceService::find($id);
        if ($inv['status'] !== 'draft' || $inv['sale_id']) {
            flash('warning', 'Only draft invoices can be edited.');
            Response::redirect('/invoices/' . $id);
        }
        $this->form($inv, $inv);
    }

    public function update(int $id): never
    {
        $this->authorize('invoices.edit');
        InvoiceService::update($id, Request::post());
        if (Request::str('save_as') === 'issue') {
            InvoiceService::issue($id);
        }
        $this->success('Invoice updated.', '/invoices/' . $id);
    }

    public function issue(int $id): never
    {
        $this->authorize('invoices.edit');
        InvoiceService::issue($id);
        $this->success('Invoice issued.', '/invoices/' . $id);
    }

    public function cancel(int $id): never
    {
        $this->authorize('invoices.delete');
        InvoiceService::cancel($id, Request::str('reason') ?: null);
        $this->success('Invoice cancelled.', '/invoices/' . $id);
    }

    public function print(int $id): never
    {
        $this->authorize('invoices.print');
        $inv = InvoiceService::find($id);
        render('invoices/print', ['inv' => $inv, 'company' => Tenant::company(), 'title' => 'Invoice ' . $inv['invoice_number'], 'docTitle' => 'Invoice'], 'layouts/print');
    }

    private function form(?array $inv, array $rec): never
    {
        render('invoices/form', [
            'title' => $inv ? 'Edit Invoice ' . $inv['invoice_number'] : 'New Invoice',
            'breadcrumbs' => ['Invoices' => '/invoices', ($inv ? $inv['invoice_number'] : 'New') => null],
            'inv' => $inv, 'rec' => $rec, 'items' => $inv['items'] ?? [],
            'customers' => CustomerService::options(isset($rec['customer_id']) ? (int) $rec['customer_id'] : null),
            'projects' => ProjectService::options(),
        ]);
    }
}
