<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\AuthService;
use App\Services\CustomerService;
use App\Services\FundService;
use App\Services\RoleService;
use App\Services\Tenant;
use App\Services\UserService;

final class UserController extends Controller
{
    public function index(): never
    {
        $this->authorize('users.view');
        $f = Request::filters(['q', 'status', 'role', 'sort', 'dir']);
        $this->view('users/index', [
            'title' => 'Users', 'breadcrumbs' => ['Settings' => null, 'Users' => null],
            'f' => $f, 'p' => UserService::list($f, $this->page()), 'roles' => RoleService::available(true),
        ], 'users/_list');
    }

    public function create(): never
    {
        $this->authorize('users.create');
        $this->form(null);
    }

    public function store(): never
    {
        $this->authorize('users.create');
        $id = UserService::save(Request::post());
        $this->success('User created.', '/users/' . $id);
    }

    public function show(int $id): never
    {
        $this->authorize('users.view');
        $user = UserService::find($id);
        render('users/show', [
            'title' => $user['name'], 'breadcrumbs' => ['Users' => '/users', $user['name'] => null],
            'user' => $user, 'roles' => RoleService::available(true), 'logins' => UserService::loginHistory($id),
            'customer' => CustomerService::forUser($id), 'funds' => can('funds.view') ? FundService::summaryForUser($id) : null,
        ]);
    }

    public function edit(int $id): never
    {
        $this->authorize('users.edit');
        $this->form(UserService::find($id));
    }

    public function update(int $id): never
    {
        $this->authorize('users.edit');
        UserService::save(Request::post(), $id);
        $this->success('User updated.', '/users/' . $id);
    }

    public function password(int $id): never
    {
        $this->authorize('users.edit');
        UserService::resetPassword($id, Request::post());
        $this->success('Password reset. The user has been signed out of all sessions.', '/users/' . $id);
    }

    public function status(int $id): never
    {
        $this->authorize('users.delete');
        $status = Request::str('status') === 'active' ? 'active' : 'inactive';
        UserService::setStatus($id, $status);
        $this->success($status === 'active' ? 'User activated.' : 'User deactivated.', '/users/' . $id);
    }

    public function destroy(int $id): never
    {
        $this->authorize('users.delete');
        UserService::delete($id);
        $this->success('User deleted.', '/users');
    }

    private function form(?array $user): never
    {
        render('users/form', [
            'title' => $user ? 'Edit User' : 'New User',
            'breadcrumbs' => ['Users' => '/users', ($user ? 'Edit' : 'New') => null],
            'user' => $user,
            'roles' => RoleService::available(AuthService::isSuperAdmin()),
            'companies' => AuthService::isSuperAdmin() ? Tenant::allowedCompanies() : [],
        ]);
    }
}
