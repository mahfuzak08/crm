<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Services\CustomerService;
use App\Services\DirectorService;
use App\Services\Tenant;

final class DirectorController extends Controller
{
    public function index(): never
    {
        $this->authorize('directors.view');
        render('directors/index', [
            'title' => 'Board of Directors', 'breadcrumbs' => ['CRM' => null, 'Board of Directors' => null],
            'directors' => DirectorService::all([Tenant::id()]),
        ]);
    }

    public function create(): never
    {
        $this->authorize('directors.manage');
        $rec = ['status' => 'active', 'source' => 'customer', 'designation' => 'Director'];
        if ($cid = Request::int('customer')) {
            $rec = ['source' => 'customer', 'customer_id' => $cid] + $rec;
        } elseif ($eid = Request::int('employee')) {
            $rec = ['source' => 'employee', 'employee_id' => $eid] + $rec;
        }
        $this->form(null, $rec);
    }

    public function store(): never
    {
        $this->authorize('directors.manage');
        $id = DirectorService::save(Request::post());
        $this->success('Director added to the board.', '/directors/' . $id);
    }

    public function show(int $id): never
    {
        $this->authorize('directors.view');
        $d = DirectorService::find($id);
        render('directors/show', [
            'title' => $d['name'], 'breadcrumbs' => ['Board of Directors' => '/directors', $d['name'] => null], 'd' => $d,
            'referrals' => can('sales.view') ? DirectorService::referrals($id) : [],
            'purchases' => can('sales.view') ? DirectorService::ownPurchases($d) : [],
        ]);
    }

    public function edit(int $id): never
    {
        $this->authorize('directors.manage');
        $d = DirectorService::find($id);
        $this->form($d, $d);
    }

    public function update(int $id): never
    {
        $this->authorize('directors.manage');
        DirectorService::save(Request::post(), $id);
        $this->success('Director updated.', '/directors/' . $id);
    }

    public function destroy(int $id): never
    {
        $this->authorize('directors.manage');
        DirectorService::delete($id);
        $this->success('Director removed from the board.', '/directors');
    }

    private function form(?array $d, array $rec): never
    {
        render('directors/form', [
            'title' => $d ? 'Edit Director' : 'Add Director',
            'breadcrumbs' => ['Board of Directors' => '/directors', ($d ? $d['name'] : 'Add') => null],
            'd' => $d, 'rec' => $rec,
            'customers' => CustomerService::options(isset($rec['customer_id']) ? (int) $rec['customer_id'] : null),
            'employees' => Database::pairs("SELECT id, CONCAT(name, ' (', employee_code, ')') FROM employees WHERE company_id = ? AND deleted_at IS NULL AND status = 'active' ORDER BY name", [Tenant::id()]),
        ]);
    }
}
