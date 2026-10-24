<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Query;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\Tenant;

final class AuditLogController extends Controller
{
    private function query(array $f): Query
    {
        $q = Query::table('audit_logs a')
            ->select('a.*, u.name AS user_name')
            ->join('LEFT JOIN users u ON u.id = a.user_id');
        // Super Admin also sees system-level entries (no company).
        AuthService::isSuperAdmin()
            ? $q->where('(a.company_id = ? OR a.company_id IS NULL)', Tenant::id())
            : $q->where('a.company_id = ?', Tenant::id());
        return $q->whereIf($f['user'], 'a.user_id = ?', (int) $f['user'])
            ->whereIf($f['module'], 'a.module = ?')
            ->whereIf($f['action'], 'a.action = ?')
            ->dateRange('DATE(a.created_at)', $f['from'], $f['to'])
            ->search(['a.action', 'a.module', 'a.ip', 'a.new_values'], $f['q'])
            ->orderBy('a.id DESC');
    }

    public function index(): never
    {
        $this->authorize('audit_logs.view');
        $f = Request::filters(['q', 'user', 'module', 'action', 'from', 'to']);
        if (Request::str('export') === 'csv') {
            $rows = (function () use ($f) {
                foreach ($this->query($f)->cursor() as $r) {
                    yield [$r['created_at'], $r['user_name'], $r['action'], $r['module'], $r['record_id'], $r['ip'], $r['old_values'], $r['new_values']];
                }
            })();
            Response::csv('audit-log-' . date('Ymd') . '.csv', ['Date', 'User', 'Action', 'Module', 'Record', 'IP', 'Old', 'New'], $rows);
        }
        $cid = Tenant::id();
        $this->view('audit/index', [
            'title' => 'Audit Logs', 'breadcrumbs' => ['System' => null, 'Audit Logs' => null], 'f' => $f,
            'p' => $this->query($f)->paginate($this->page(), 30),
            'users' => Database::pairs('SELECT id, name FROM users WHERE company_id = ? ORDER BY name', [$cid]),
            'modules' => array_column(Database::fetchAll('SELECT DISTINCT module FROM audit_logs WHERE company_id = ? ORDER BY module', [$cid]), 'module'),
            'actions' => array_column(Database::fetchAll('SELECT DISTINCT action FROM audit_logs WHERE company_id = ? ORDER BY action', [$cid]), 'action'),
        ], 'audit/_list');
    }
}
