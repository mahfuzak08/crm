<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\DirectorService;
use App\Services\EmployeeService;
use App\Services\FileUploadService;
use App\Services\MasterDataService;
use App\Services\SalaryService;
use App\Services\Tenant;
use App\Services\UserService;

final class EmployeeController extends Controller
{
    public function index(): never
    {
        $this->authorize('employees.view');
        $f = Request::filters(['q', 'status', 'department', 'designation', 'sort', 'dir']);
        $this->view('employees/index', [
            'title' => 'Employees', 'breadcrumbs' => ['HR' => null, 'Employees' => null], 'f' => $f,
            'p' => EmployeeService::query($f)->paginate($this->page()),
            'departments' => MasterDataService::options('department'), 'designations' => MasterDataService::options('designation'),
        ], 'employees/_list');
    }

    public function create(): never
    {
        $this->authorize('employees.create');
        $this->form(null);
    }

    public function store(): never
    {
        $this->authorize('employees.create');
        $id = EmployeeService::save(Request::post());
        $this->success('Employee created.', '/employees/' . $id);
    }

    public function show(int $id): never
    {
        $this->authorize('employees.view');
        $e = EmployeeService::find($id);
        render('employees/show', [
            'title' => $e['name'], 'breadcrumbs' => ['Employees' => '/employees', $e['employee_code'] => null], 'e' => $e,
            'director' => DirectorService::forRecord('employee_id', $id),
            'salary' => can('salary.view') ? SalaryService::compute($e) : null,
            'payments' => can('salary.view') ? Database::fetchAll('SELECT * FROM salary_payments WHERE employee_id = ? AND company_id = ? ORDER BY salary_month DESC LIMIT 24', [$id, Tenant::id()]) : [],
        ]);
    }

    public function edit(int $id): never
    {
        $this->authorize('employees.edit');
        $this->form(EmployeeService::find($id));
    }

    public function update(int $id): never
    {
        $this->authorize('employees.edit');
        EmployeeService::save(Request::post(), $id);
        $this->success('Employee updated.', '/employees/' . $id);
    }

    public function destroy(int $id): never
    {
        $this->authorize('employees.delete');
        EmployeeService::delete($id);
        $this->success('Employee deleted.', '/employees');
    }

    public function photo(int $id): never
    {
        $this->authorize('employees.view');
        $e = EmployeeService::find($id);
        if (!$e['photo']) {
            throw new HttpException(404);
        }
        $ext = strtolower(pathinfo((string) $e['photo'], PATHINFO_EXTENSION));
        Response::file(FileUploadService::absolute((string) $e['photo']), 'photo.' . $ext, FileUploadService::MIME_BY_EXT[$ext] ?? 'image/jpeg', true);
    }

    private function form(?array $e): never
    {
        render('employees/form', [
            'title' => $e ? 'Edit Employee' : 'New Employee', 'breadcrumbs' => ['Employees' => '/employees', ($e ? $e['employee_code'] : 'New') => null],
            'e' => $e, 'departments' => MasterDataService::options('department'), 'designations' => MasterDataService::options('designation'),
            'users' => UserService::options(), 'components' => can('salary.manage') ? SalaryService::components(true) : [],
            'structure' => $e ? SalaryService::structure((int) $e['id']) : [],
        ]);
    }
}
