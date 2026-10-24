<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\ValidationException;
use App\Core\Validator;
use App\Services\AuditLogService;
use App\Services\AuthService;

final class ProfileController extends Controller
{
    public function show(): never
    {
        $user = Database::fetch('SELECT id, name, email, phone, username, last_login_at, last_login_ip, created_at FROM users WHERE id = ?', [auth_id()]);
        $logins = Database::fetchAll('SELECT ip, user_agent, success, created_at FROM login_attempts WHERE user_id = ? ORDER BY id DESC LIMIT 10', [auth_id()]);
        render('profile/show', ['title' => 'My Profile', 'user' => $user, 'logins' => $logins, 'breadcrumbs' => ['Profile' => null]]);
    }

    public function update(): never
    {
        $id = (int) auth_id();
        $data = Validator::make(Request::post(), [
            'name'  => 'required|max:150',
            'email' => ['nullable', 'email', static fn($v) => Database::value('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$v, $id]) ? 'Email already in use.' : null],
            'phone' => ['nullable', 'phone', static fn($v) => Database::value('SELECT 1 FROM users WHERE phone = ? AND id <> ?', [$v, $id]) ? 'Phone already in use.' : null],
        ]);
        $old = Database::fetch('SELECT name, email, phone FROM users WHERE id = ?', [$id]);
        Database::update('users', $data, 'id = ?', [$id]);
        AuditLogService::log('UPDATE_PROFILE', 'users', $id, $old, $data);
        $this->success('Profile updated.', '/profile');
    }

    public function showPassword(): never
    {
        render('profile/password', ['title' => 'Change Password', 'breadcrumbs' => ['Profile' => '/profile', 'Change Password' => null]]);
    }

    public function updatePassword(): never
    {
        Validator::make(Request::post(), [
            'current_password' => 'required',
            'password' => 'required|password',
            'password_confirmation' => 'required|same:password',
        ], ['password_confirmation' => 'Password']);
        $hash = (string) Database::value('SELECT password FROM users WHERE id = ?', [auth_id()]);
        if (!password_verify((string) $_POST['current_password'], $hash)) {
            throw new ValidationException(['current_password' => 'Current password is incorrect.']);
        }
        AuthService::changePassword((int) auth_id(), (string) $_POST['password']); // signs out other sessions
        AuditLogService::log('CHANGE_PASSWORD', 'users', auth_id());
        $this->success('Password changed. Other sessions have been signed out.', '/profile');
    }
}
