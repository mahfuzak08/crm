<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use App\Core\Validator;
use App\Services\CompanyService;
use App\Services\FileUploadService;
use App\Services\InstallService;
use PDO;
use Throwable;

/**
 * Two-step web installer:
 *  1. database credentials -> .env written (or shown for manual creation)
 *  2. company + Super Admin -> migrations, seed, lock file
 * The installer routes are not registered at all once storage/installed.lock exists.
 */
final class InstallController extends Controller
{
    public function index(): never
    {
        $requirements = InstallService::requirements();
        $dbOk = $this->dbConnects();
        render('install/index', [
            'title' => 'Installation',
            'requirements' => $requirements,
            'allOk' => !array_filter($requirements, static fn($c) => $c[2] && !$c[1]),
            'step' => $dbOk ? 2 : 1,
            'envContent' => Session::getFlash('env_content'),
            'wide' => true,
        ], 'layouts/guest');
    }

    public function database(): never
    {
        $d = Validator::make(Request::post(), [
            'db_host' => 'required|max:100',
            'db_port' => 'required|integer',
            'db_name' => ['required', 'max:64', static fn($v) => preg_match('/^[A-Za-z0-9_]+$/', $v) ? null : 'Letters, numbers and underscore only.'],
            'db_user' => 'required|max:100',
            'db_pass' => 'nullable|max:200',
            'app_url' => 'required|url|max:200',
        ], ['db_name' => 'Database name', 'db_user' => 'Database user', 'app_url' => 'Application URL']);

        $db = ['host' => $d['db_host'], 'port' => (int) $d['db_port'], 'database' => $d['db_name'], 'username' => $d['db_user'], 'password' => (string) ($_POST['db_pass'] ?? '')];
        try {
            $pdo = Database::connect($db['host'], $db['port'], '', $db['username'], $db['password']);
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $db['database'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo->exec('USE `' . $db['database'] . '`');
        } catch (Throwable $e) {
            Logger::error('Installer DB connection failed: ' . $e->getMessage());
            throw ValidationException::rule('Could not connect to the database or create it. Check the credentials (details in storage/logs).');
        }

        $appUrl = rtrim($d['app_url'], '/');
        try {
            InstallService::writeEnv($db, $appUrl);
            flash('success', 'Database connected and .env written.');
        } catch (Throwable) {
            // Project root not writable: show content so the admin can create .env manually.
            Session::flash('env_content', $this->envPreview($db, $appUrl));
            flash('warning', 'Could not write .env (folder not writable). Create it manually with the content shown, then reload this page.');
        }
        Response::redirect('/install');
    }

    public function setup(): never
    {
        if (!$this->dbConnects()) {
            throw ValidationException::rule('Database is not configured yet.');
        }
        $in = Request::post();
        $company = CompanyService::validate([
            'name' => $in['company_name'] ?? '', 'address' => $in['company_address'] ?? '',
            'phone' => $in['company_phone'] ?? '', 'email' => $in['company_email'] ?? '',
            'website' => $in['company_website'] ?? '',
        ]);
        $admin = Validator::make($in, [
            'admin_name'     => 'required|max:150',
            'admin_email'    => 'required|email',
            'admin_phone'    => 'nullable|phone',
            'admin_username' => 'required|username',
            'admin_password' => 'required|password',
            'admin_password_confirmation' => 'required|same:admin_password',
        ], ['admin_password_confirmation' => 'Password']);

        if ($file = Request::file('company_logo')) {
            $company['logo'] = FileUploadService::store($file, ['image'], 'company_logo', 2)['stored_name'];
        }

        $pdo = Database::pdo();
        InstallService::migrate($pdo);
        if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
            InstallService::lock();
            throw ValidationException::rule('This database already contains an installation. The installer has been locked.');
        }
        InstallService::seed($company, [
            'name' => $admin['admin_name'], 'email' => $admin['admin_email'], 'phone' => $admin['admin_phone'],
            'username' => $admin['admin_username'], 'password' => (string) $_POST['admin_password'],
        ]);
        InstallService::lock();
        Logger::app('Application installed', ['company' => $company['name']]);

        flash('success', 'Installation complete. Sign in with your Super Admin account.');
        Response::redirect('/login');
    }

    private function dbConnects(): bool
    {
        if (!is_file(BASE_PATH . '/.env') || (string) config('db.database') === '') {
            return false;
        }
        try {
            Database::pdo()->query('SELECT 1');
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function envPreview(array $db, string $appUrl): string
    {
        $q = static fn($v) => '"' . addcslashes((string) $v, "\"\\\$") . '"';
        return implode("\n", [
            'APP_NAME="Lake View Iconic Tower CRM"', 'APP_ENV=production', 'APP_DEBUG=false',
            'APP_URL=' . $q($appUrl), 'APP_KEY=' . bin2hex(random_bytes(32)), 'APP_TIMEZONE=Asia/Dhaka',
            'DB_HOST=' . $q($db['host']), 'DB_PORT=' . $db['port'], 'DB_DATABASE=' . $q($db['database']),
            'DB_USERNAME=' . $q($db['username']), 'DB_PASSWORD=' . $q($db['password']),
            'SESSION_NAME=lakecrm', 'LOGIN_FIELDS=email,username,phone', 'MAIL_DRIVER=none',
        ]);
    }
}
