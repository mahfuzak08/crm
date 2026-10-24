<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\ValidationException;
use App\Services\AuditLogService;
use App\Services\CompanyService;
use App\Services\Tenant;

/** Multi-company administration (Super Admin / companies.manage). */
final class CompanyController extends Controller
{
    public function index(): never
    {
        $this->authorize('companies.manage');
        $rows = Database::fetchAll(
            "SELECT c.*, (SELECT COUNT(*) FROM users u WHERE u.company_id = c.id AND u.deleted_at IS NULL) AS users_count,
                    (SELECT COUNT(*) FROM projects p WHERE p.company_id = c.id AND p.deleted_at IS NULL) AS projects_count
             FROM companies c WHERE c.deleted_at IS NULL ORDER BY c.name"
        );
        render('companies/index', ['title' => 'Companies', 'breadcrumbs' => ['Settings' => null, 'Companies' => null], 'rows' => $rows]);
    }

    public function create(): never
    {
        $this->authorize('companies.manage');
        render('companies/form', ['title' => 'New Company', 'breadcrumbs' => ['Companies' => '/companies', 'New' => null], 'company' => null]);
    }

    public function store(): never
    {
        $this->authorize('companies.manage');
        $data = CompanyService::validate(Request::post());
        CompanyService::create($data);
        $this->success('Company created with default configuration. Switch to it from the top bar to configure.', '/companies');
    }

    public function edit(int $id): never
    {
        $this->authorize('companies.manage');
        $company = Database::fetch('SELECT * FROM companies WHERE id = ? AND deleted_at IS NULL', [$id]) ?? throw new HttpException(404);
        render('companies/form', ['title' => 'Edit Company', 'breadcrumbs' => ['Companies' => '/companies', 'Edit' => null], 'company' => $company]);
    }

    public function update(int $id): never
    {
        $this->authorize('companies.manage');
        $old = Database::fetch('SELECT * FROM companies WHERE id = ? AND deleted_at IS NULL', [$id]) ?? throw new HttpException(404);
        $data = CompanyService::validate(Request::post(), $id);
        $data['status'] = Request::str('status') === 'inactive' ? 'inactive' : 'active';
        if ($data['status'] === 'inactive' && $id === Tenant::id()) {
            throw ValidationException::rule('You cannot deactivate the company you are currently working in.');
        }
        Database::update('companies', $data, 'id = ?', [$id]);
        AuditLogService::log('UPDATE_COMPANY', 'companies', $id, $old, $data, $id);
        $this->success('Company updated.', '/companies');
    }

    /** Super Admin: change active company context. */
    public function switch(): never
    {
        $id = Request::int('company_id');
        Tenant::switchTo($id);
        AuditLogService::log('SWITCH_COMPANY', 'companies', $id);
        flash('success', 'Switched company.');
        Response::redirect('/');
    }
}
