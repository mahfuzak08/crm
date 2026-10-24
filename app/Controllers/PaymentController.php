<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\InvoiceService;
use App\Services\MasterDataService;
use App\Services\PaymentService;
use App\Services\ProjectService;
use App\Services\Tenant;

final class PaymentController extends Controller
{
    public function index(): never
    {
        $this->authorize('payments.view');
        $f = Request::filters(['q', 'status', 'method', 'customer', 'project', 'from', 'to', 'sort', 'dir', 'view']);
        if (Request::str('export') === 'csv') {
            $this->authorize('reports.export');
            $rows = (static function () use ($f) {
                foreach (PaymentService::query($f)->cursor() as $r) {
                    yield [$r['payment_number'], $r['payment_date'], $r['customer_name'], $r['invoice_number'], $r['method_name'], $r['reference'], $r['amount'], $r['status'], $r['received_by_name']];
                }
            })();
            Response::csv('payments-' . date('Ymd') . '.csv', ['Receipt', 'Date', 'Customer', 'Invoice', 'Method', 'Reference', 'Amount', 'Status', 'Received by'], $rows);
        }
        $receipts = $f['view'] === 'receipts';
        $this->view('payments/index', [
            'title' => $receipts ? 'Receipts' : 'Payments', 'breadcrumbs' => ['Sales' => null, ($receipts ? 'Receipts' : 'Payments') => null], 'f' => $f,
            'p' => PaymentService::query($f)->paginate($this->page()),
            'total' => PaymentService::query($f)->where("p.status = 'confirmed'")->aggregate(['sum' => 'COALESCE(SUM(p.amount),0)'])['sum'] ?? 0,
            'methods' => MasterDataService::options('payment_method', false), 'projects' => ProjectService::options(),
        ], 'payments/_list');
    }

    public function create(): never
    {
        $this->authorize('payments.create');
        $invoiceId = Request::int('invoice');
        $customerId = null;
        if ($invoiceId) {
            $customerId = (int) InvoiceService::find($invoiceId)['customer_id'];
        }
        render('payments/form', [
            'title' => 'Receive Payment', 'breadcrumbs' => ['Payments' => '/payments', 'Receive' => null],
            'invoices' => InvoiceService::openForCustomer($customerId),
            'rec' => ['invoice_id' => $invoiceId ?: '', 'payment_date' => today(), 'idempotency_key' => bin2hex(random_bytes(16))],
            'methods' => MasterDataService::options('payment_method'),
        ]);
    }

    public function store(): never
    {
        $this->authorize('payments.create');
        $id = PaymentService::create(Request::post());
        $this->success('Payment received.', '/payments/' . $id);
    }

    public function show(int $id): never
    {
        $this->authorize('payments.view');
        $p = PaymentService::find($id);
        render('payments/show', ['title' => 'Payment ' . $p['payment_number'], 'breadcrumbs' => ['Payments' => '/payments', $p['payment_number'] => null], 'p' => $p]);
    }

    public function update(int $id): never
    {
        $this->authorize('payments.edit');
        PaymentService::updateMeta($id, Request::post());
        $this->success('Payment updated.', '/payments/' . $id);
    }

    public function cancel(int $id): never
    {
        $this->authorize('payments.delete');
        PaymentService::cancel($id, Request::str('reason') ?: null);
        $this->success('Payment cancelled and invoice balance restored.', '/payments/' . $id);
    }

    public function receipt(int $id): never
    {
        $this->authorize('payments.view');
        $p = PaymentService::find($id);
        render('payments/receipt', ['p' => $p, 'company' => Tenant::company(), 'title' => 'Receipt ' . $p['payment_number'], 'docTitle' => 'Money Receipt'], 'layouts/print');
    }
}
