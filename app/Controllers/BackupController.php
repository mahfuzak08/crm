<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuditLogService;
use App\Services\BackupService;

/** Database backups — routes require Super Admin + backup.manage. */
final class BackupController extends Controller
{
    public function index(): never
    {
        $this->authorize('backup.manage');
        render('backup/index', ['title' => 'Database Backup', 'breadcrumbs' => ['System' => null, 'Backup' => null], 'files' => BackupService::list()]);
    }

    public function store(): never
    {
        $this->authorize('backup.manage');
        $name = BackupService::create();
        $this->success('Backup created: ' . $name, '/backup');
    }

    public function download(): never
    {
        $this->authorize('backup.manage');
        $name = Request::str('file');
        $path = BackupService::path($name);
        AuditLogService::log('DOWNLOAD_BACKUP', 'system', null, null, ['file' => $name]);
        Response::file($path, $name, 'application/gzip');
    }

    public function destroy(): never
    {
        $this->authorize('backup.manage');
        BackupService::delete(Request::str('file'));
        $this->success('Backup deleted.', '/backup');
    }
}
