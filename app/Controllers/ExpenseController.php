<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\ExpenseService;
use App\Services\FileUploadService;
use App\Services\MasterDataService;
use App\Services\ProjectService;
use App\Services\Tenant;

final class ExpenseController extends Controller
{
    public function index(): never
    {
        $this->authorize('expenses.view');
        $f = Request::filters(['q', 'category', 'status', 'project', 'from', 'to', 'sort', 'dir']);
        if (Request::str('export') === 'csv') {
            $this->authorize('reports.export');
            $rows = (static function () use ($f) {
                foreach (ExpenseService::query($f)->cursor() as $r) {
                    yield [$r['expense_number'], $r['expense_date'], $r['category_name'], $r['project_name'], $r['method_name'], $r['reference'], $r['amount'], $r['status'], $r['description']];
                }
            })();
            Response::csv('expenses-' . date('Ymd') . '.csv', ['Number', 'Date', 'Category', 'Project', 'Method', 'Reference', 'Amount', 'Status', 'Description'], $rows);
        }
        $this->view('expenses/index', [
            'title' => 'Expenses', 'breadcrumbs' => ['Finance' => null, 'Expenses' => null], 'f' => $f,
            'p' => ExpenseService::query($f)->paginate($this->page()),
            'total' => ExpenseService::query($f)->where("x.status = 'approved'")->aggregate(['sum' => 'COALESCE(SUM(x.amount),0)'])['sum'] ?? 0,
            'categories' => MasterDataService::options('expense_category', false), 'projects' => ProjectService::options(),
        ], 'expenses/_list');
    }

    public function create(): never
    {
        $this->authorize('expenses.create');
        $this->form(null);
    }

    public function store(): never
    {
        $this->authorize('expenses.create');
        $id = ExpenseService::save(Request::post());
        $this->success('Expense recorded.', '/expenses/' . $id);
    }

    public function show(int $id): never
    {
        $this->authorize('expenses.view');
        $x = ExpenseService::find($id);
        render('expenses/show', ['title' => 'Expense ' . $x['expense_number'], 'breadcrumbs' => ['Expenses' => '/expenses', $x['expense_number'] => null], 'x' => $x]);
    }

    public function edit(int $id): never
    {
        $this->authorize('expenses.edit');
        $this->form(ExpenseService::find($id));
    }

    public function update(int $id): never
    {
        $this->authorize('expenses.edit');
        ExpenseService::save(Request::post(), $id);
        $this->success('Expense updated.', '/expenses/' . $id);
    }

    public function approve(int $id): never
    {
        $this->authorize('expenses.approve');
        ExpenseService::approve($id);
        $this->success('Expense approved.', '/expenses/' . $id);
    }

    public function cancel(int $id): never
    {
        $this->authorize('expenses.delete');
        ExpenseService::cancel($id, Request::str('reason') ?: null);
        $this->success('Expense cancelled.', '/expenses/' . $id);
    }

    public function attachment(int $id): never
    {
        $this->authorize('expenses.view');
        $x = ExpenseService::find($id);
        if (!$x['attachment']) {
            throw new HttpException(404);
        }
        Response::file(FileUploadService::absolute($x['attachment']), (string) $x['attachment_name'], (string) $x['attachment_mime'], true);
    }

    public function voucher(int $id): never
    {
        $this->authorize('expenses.view');
        $x = ExpenseService::find($id);
        render('expenses/voucher', ['x' => $x, 'company' => Tenant::company(), 'title' => 'Voucher ' . $x['expense_number'], 'docTitle' => 'Expense Voucher'], 'layouts/print');
    }

    private function form(?array $x): never
    {
        render('expenses/form', [
            'title' => $x ? 'Edit Expense' : 'New Expense', 'breadcrumbs' => ['Expenses' => '/expenses', ($x ? $x['expense_number'] : 'New') => null],
            'x' => $x ?? ['expense_date' => today()], 'isEdit' => (bool) $x,
            'categories' => MasterDataService::options('expense_category'), 'methods' => MasterDataService::options('payment_method'),
            'projects' => ProjectService::options(),
        ]);
    }
}
