<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\DashboardService;
use App\Services\ReportService;
use App\Services\Tenant;

final class DashboardController extends Controller
{
    public function index(): never
    {
        if (!can('dashboard.view')) {
            render('dashboard/welcome', ['title' => 'Welcome']);
        }
        $scope = AuthService::isSuperAdmin() ? Request::str('company') : '';
        $cids = ReportService::companyScope($scope);
        render('dashboard/index', [
            'title' => 'Dashboard',
            'scope' => $scope, 'companies' => AuthService::isSuperAdmin() ? Tenant::allowedCompanies() : [],
            'd' => DashboardService::data($cids), 'lists' => DashboardService::lists(),
        ]);
    }

    /** Mark an in-app notification read and follow its link. */
    public function notification(int $id): never
    {
        $n = Database::fetch('SELECT * FROM notifications WHERE id = ? AND company_id = ? AND user_id = ?', [$id, Tenant::id(), AuthService::id()]);
        if ($n) {
            Database::update('notifications', ['read_at' => now(), 'status' => 'read'], 'id = ?', [$id]);
        }
        $link = (string) ($n['link'] ?? '/');
        Response::redirect(preg_match('#^/[A-Za-z0-9/_\-?=&]*$#', $link) ? $link : '/');
    }
}
