<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\RoleService;

final class RoleController extends Controller
{
    public function index(): never
    {
        $this->authorize('roles.view');
        render('roles/index', ['title' => 'Roles & Permissions', 'breadcrumbs' => ['Settings' => null, 'Roles' => null], 'roles' => RoleService::available(true)]);
    }

    public function create(): never
    {
        $this->authorize('roles.create');
        $this->form(null);
    }

    public function store(): never
    {
        $this->authorize('roles.create');
        RoleService::save(Request::post());
        $this->success('Role created.', '/roles');
    }

    public function edit(int $id): never
    {
        $this->authorize('roles.view');
        $this->form(RoleService::find($id));
    }

    public function update(int $id): never
    {
        $this->authorize('roles.edit');
        RoleService::save(Request::post(), $id);
        $this->success('Role updated. Changes apply immediately.', '/roles');
    }

    public function destroy(int $id): never
    {
        $this->authorize('roles.delete');
        RoleService::delete($id);
        $this->success('Role deleted.', '/roles');
    }

    private function form(?array $role): never
    {
        $readonly = $role && ($role['slug'] === 'super_admin' || ($role['company_id'] === null && !\App\Services\AuthService::isSuperAdmin()) || !can('roles.edit'));
        render('roles/form', [
            'title' => $role ? 'Role: ' . $role['name'] : 'New Role',
            'breadcrumbs' => ['Roles' => '/roles', ($role ? 'Edit' : 'New') => null],
            'role' => $role, 'readonly' => $readonly,
            'modules' => RoleService::permissionsByModule(),
            'selected' => $role ? RoleService::permissionIds((int) $role['id']) : [],
        ]);
    }
}
