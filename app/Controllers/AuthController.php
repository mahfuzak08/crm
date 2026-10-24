<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Services\AuthService;
use App\Services\NotificationService;

final class AuthController extends Controller
{
    public function showLogin(): never
    {
        render('auth/login', ['title' => 'Sign in'], 'layouts/guest');
    }

    public function login(): never
    {
        $in = Validator::make(Request::post(), ['identifier' => 'required|max:150', 'password' => 'required|max:200'], ['identifier' => 'Email / username / phone']);
        AuthService::attempt($in['identifier'], (string) $_POST['password']);

        $to = (string) Session::get('intended', '/');
        Session::forget('intended');
        // only allow internal relative paths
        if (!preg_match('#^/[A-Za-z0-9/_\-?=&%.]*$#', $to) || str_starts_with($to, '//')) {
            $to = '/';
        }
        Response::redirect($to);
    }

    public function logout(): never
    {
        AuthService::logout();
        Session::start();
        flash('success', 'You have been signed out.');
        Response::redirect('/login');
    }

    public function showForgot(): never
    {
        render('auth/forgot', ['title' => 'Forgot password', 'mailEnabled' => NotificationService::canEmail()], 'layouts/guest');
    }

    public function forgot(): never
    {
        $in = Validator::make(Request::post(), ['email' => 'required|email']);
        if (NotificationService::canEmail()) {
            $res = AuthService::createResetToken($in['email']);
            if ($res) {
                $company = Database::fetch('SELECT * FROM companies WHERE id = ?', [$res['user']['company_id']]);
                $link = full_url('/reset-password/' . $res['token']);
                $html = '<p>Hello ' . e($res['user']['name']) . ',</p><p>We received a request to reset your password. This link expires in '
                    . (int) config('auth.reset_token_minutes', 60) . ' minutes.</p>'
                    . '<p><a href="' . e($link) . '" style="display:inline-block;padding:10px 18px;background:#0f766e;color:#fff;border-radius:6px;text-decoration:none">Reset password</a></p>'
                    . '<p style="font-size:12px;color:#6b7385">If you did not request this, ignore this email.</p>';
                NotificationService::send('email', $res['user']['email'], 'Password reset', NotificationService::emailLayout('Password reset', $html, $company), [], (int) $res['user']['company_id']);
            }
        }
        // Same response whether or not the account exists (no user enumeration).
        flash('success', 'If an account exists for that email, a reset link has been sent.');
        Response::redirect('/login');
    }

    public function showReset(string $token): never
    {
        if (!AuthService::findResetToken($token)) {
            throw new HttpException(404, 'This reset link is invalid or has expired.');
        }
        render('auth/reset', ['title' => 'Reset password', 'token' => $token], 'layouts/guest');
    }

    public function reset(string $token): never
    {
        Validator::make(Request::post(), ['password' => 'required|password', 'password_confirmation' => 'required|same:password'], ['password_confirmation' => 'Password']);
        AuthService::resetWithToken($token, (string) $_POST['password']);
        flash('success', 'Password updated. Please sign in.');
        Response::redirect('/login');
    }
}
