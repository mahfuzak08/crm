<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\ActivityService;
use App\Services\CustomerService;
use App\Services\DirectorService;
use App\Services\DocumentService;
use App\Services\FileUploadService;
use App\Services\MasterDataService;
use App\Services\NotificationService;
use App\Services\Tenant;

final class CustomerController extends Controller
{
    public function index(): never
    {
        $this->authorize('customers.view');
        $f = Request::filters(['q', 'status', 'type', 'from', 'to', 'sort', 'dir']);
        if (Request::str('export') === 'csv') {
            $this->authorize('reports.export');
            $rows = (static function () use ($f) {
                foreach (CustomerService::query($f)->cursor() as $r) {
                    yield [$r['customer_code'], $r['name'], $r['phone'], $r['email'], $r['type_name'], $r['city'], $r['district'], $r['shares'], $r['due'], $r['status']];
                }
            })();
            Response::csv('customers-' . date('Ymd') . '.csv', ['Code', 'Name', 'Phone', 'Email', 'Type', 'City', 'District', 'Shares', 'Due', 'Status'], $rows);
        }
        $this->view('customers/index', [
            'title' => 'Customers', 'breadcrumbs' => ['CRM' => null, 'Customers' => null], 'f' => $f,
            'p' => CustomerService::query($f)->paginate($this->page()), 'types' => MasterDataService::options('customer_type'),
        ], 'customers/_list');
    }

    public function create(): never
    {
        $this->authorize('customers.create');
        $this->form(null);
    }

    public function store(): never
    {
        $this->authorize('customers.create');
        $id = CustomerService::save(Request::post());
        $this->success('Customer created.', '/customers/' . $id);
    }

    public function show(int $id): never
    {
        $this->authorize('customers.view');
        $c = CustomerService::find($id);
        $cid = Tenant::id();
        render('customers/show', [
            'title' => $c['name'], 'breadcrumbs' => ['Customers' => '/customers', $c['customer_code'] => null],
            'c' => $c, 'summary' => CustomerService::summary($id), 'director' => DirectorService::forRecord('customer_id', $id),
            'sales' => can('sales.view') ? Database::fetchAll('SELECT s.*, p.project_name, d.name AS director_name FROM share_sales s JOIN projects p ON p.id = s.project_id LEFT JOIN directors d ON d.id = s.director_id WHERE s.customer_id = ? AND s.company_id = ? ORDER BY s.id DESC', [$id, $cid]) : [],
            'invoices' => can('invoices.view') ? Database::fetchAll('SELECT * FROM invoices WHERE customer_id = ? AND company_id = ? AND deleted_at IS NULL ORDER BY id DESC', [$id, $cid]) : [],
            'payments' => can('payments.view') ? Database::fetchAll('SELECT p.*, i.invoice_number, m.name AS method FROM payments p JOIN invoices i ON i.id = p.invoice_id JOIN master_data m ON m.id = p.payment_method_id WHERE p.customer_id = ? AND p.company_id = ? ORDER BY p.id DESC', [$id, $cid]) : [],
            'offers' => can('offers.view') ? Database::fetchAll('SELECT id, offer_number, total, status, created_at FROM offers WHERE customer_id = ? AND company_id = ? AND deleted_at IS NULL ORDER BY id DESC', [$id, $cid]) : [],
            'timeline' => can('activities.view') ? ActivityService::timeline('customer', $id) : [],
            'activityTypes' => MasterDataService::options('activity_type'),
            'documents' => can('documents.view') ? DocumentService::forSubject('customer_id', $id) : [],
            'docTypes' => MasterDataService::options('document_type'),
            'whatsapp' => NotificationService::whatsappLink($c['phone']),
        ]);
    }

    public function edit(int $id): never
    {
        $this->authorize('customers.edit');
        $this->form(CustomerService::find($id));
    }

    public function update(int $id): never
    {
        $this->authorize('customers.edit');
        CustomerService::save(Request::post(), $id);
        $this->success('Customer updated.', '/customers/' . $id);
    }

    public function destroy(int $id): never
    {
        $this->authorize('customers.delete');
        CustomerService::delete($id);
        $this->success('Customer deleted.', '/customers');
    }

    /** Access-controlled photo / NID image. */
    public function file(int $id, string $field): never
    {
        $this->authorize('customers.view');
        if (!isset(CustomerService::FILE_FIELDS[$field])) {
            throw new HttpException(404);
        }
        $c = CustomerService::find($id);
        if (!$c[$field]) {
            throw new HttpException(404);
        }
        $ext = strtolower(pathinfo((string) $c[$field], PATHINFO_EXTENSION));
        Response::file(FileUploadService::absolute((string) $c[$field]), $c['customer_code'] . '-' . $field . '.' . $ext, FileUploadService::MIME_BY_EXT[$ext] ?? 'application/octet-stream', true);
    }

    private function form(?array $c): never
    {
        render('customers/form', [
            'title' => $c ? 'Edit Customer' : 'New Customer',
            'breadcrumbs' => ['Customers' => '/customers', ($c ? $c['customer_code'] : 'New') => null],
            'c' => $c, 'types' => MasterDataService::options('customer_type'),
        ]);
    }
}
