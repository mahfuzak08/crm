<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Services\EmployeeService;
use App\Services\MasterDataService;
use App\Services\SalaryService;
use App\Services\Tenant;

final class SalaryController extends Controller
{
    public function index(): never
    {
        $this->authorize('salary.view');
        $f = Request::filters(['q', 'month', 'employee', 'status']);
        $f['month'] = preg_match('/^\d{4}-\d{2}$/', $f['month']) ? $f['month'] : '';
        $month = $f['month'] ?: date('Y-m');
        // Who is still unpaid for the selected month?
        $unpaid = Database::fetchAll(
            "SELECT e.id, e.name, e.employee_code, e.salary FROM employees e
             WHERE e.company_id = ? AND e.deleted_at IS NULL AND e.status = 'active'
               AND NOT EXISTS (SELECT 1 FROM salary_payments sp WHERE sp.employee_id = e.id AND sp.salary_month = ? AND sp.status = 'paid')
             ORDER BY e.name",
            [Tenant::id(), $month]
        );
        $this->view('salary/index', [
            'title' => 'Salary', 'breadcrumbs' => ['HR' => null, 'Salary' => null], 'f' => $f, 'month' => $month,
            'p' => SalaryService::query($f)->paginate($this->page()),
            'total' => SalaryService::query($f)->where("sp.status = 'paid'")->aggregate(['sum' => 'COALESCE(SUM(sp.net_salary),0)'])['sum'] ?? 0,
            'unpaid' => $unpaid, 'employees' => EmployeeService::options(false),
        ], 'salary/_list');
    }

    public function create(): never
    {
        $this->authorize('salary.pay');
        $empId = Request::int('employee');
        $emp = $empId ? EmployeeService::find($empId) : null;
        render('salary/form', [
            'title' => 'Pay Salary', 'breadcrumbs' => ['Salary' => '/salary', 'Pay' => null],
            'employees' => EmployeeService::options(), 'emp' => $emp, 'preview' => $emp ? SalaryService::compute($emp) : null,
            'rec' => ['employee_id' => $empId ?: '', 'salary_month' => Request::str('month') ?: date('Y-m'), 'payment_date' => today()],
            'methods' => MasterDataService::options('payment_method'),
        ]);
    }

    public function store(): never
    {
        $this->authorize('salary.pay');
        $id = SalaryService::pay(Request::post());
        $this->success('Salary paid.', '/salary/' . $id);
    }

    public function show(int $id): never
    {
        $this->authorize('salary.view');
        $p = SalaryService::find($id);
        render('salary/show', ['title' => 'Salary ' . $p['payment_number'], 'breadcrumbs' => ['Salary' => '/salary', $p['payment_number'] => null], 'p' => $p]);
    }

    public function payslip(int $id): never
    {
        $this->authorize('salary.view');
        $p = SalaryService::find($id);
        render('salary/payslip', ['p' => $p, 'company' => Tenant::company(), 'title' => 'Payslip ' . $p['payment_number'], 'docTitle' => 'Payslip'], 'layouts/print');
    }

    public function cancel(int $id): never
    {
        $this->authorize('salary.pay');
        SalaryService::cancel($id);
        $this->success('Salary payment cancelled.', '/salary/' . $id);
    }

    /* ---------- components (setup) ---------- */

    public function components(): never
    {
        $this->authorize('salary.view');
        $edit = Request::int('edit');
        $items = SalaryService::components();
        render('salary/components', [
            'title' => 'Salary Components', 'breadcrumbs' => ['Settings' => null, 'Salary Components' => null], 'items' => $items,
            'editing' => $edit ? (array_values(array_filter($items, static fn($i) => (int) $i['id'] === $edit))[0] ?? null) : null,
        ]);
    }

    public function storeComponent(): never
    {
        $this->authorize('salary.manage');
        SalaryService::saveComponent(Request::post());
        $this->success('Component saved.', '/salary/components');
    }

    public function updateComponent(int $id): never
    {
        $this->authorize('salary.manage');
        SalaryService::saveComponent(Request::post(), $id);
        $this->success('Component updated.', '/salary/components');
    }

    public function destroyComponent(int $id): never
    {
        $this->authorize('salary.manage');
        SalaryService::deleteComponent($id);
        $this->success('Component deleted.', '/salary/components');
    }

}
