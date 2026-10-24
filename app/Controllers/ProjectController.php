<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\MasterDataService;
use App\Services\ProjectService;
use App\Services\ShareService;
use App\Services\Tenant;

final class ProjectController extends Controller
{
    public function index(): never
    {
        $this->authorize('projects.view');
        $f = Request::filters(['q', 'status', 'sort', 'dir']);
        $this->view('projects/index', [
            'title' => 'Projects', 'breadcrumbs' => ['Projects' => null], 'f' => $f,
            'p' => ProjectService::list($f, $this->page()), 'statuses' => MasterDataService::options('project_status'),
        ], 'projects/_list');
    }

    public function create(): never
    {
        $this->authorize('projects.create');
        $this->form(null);
    }

    public function store(): never
    {
        $this->authorize('projects.create');
        $id = ProjectService::save(Request::post());
        $this->success('Project created.', '/projects/' . $id);
    }

    public function show(int $id): never
    {
        $this->authorize('projects.view');
        $project = ProjectService::find($id);
        $editPkg = Request::int('package') ? ShareService::findPackage(Request::int('package')) : null;
        render('projects/show', [
            'title' => $project['project_name'], 'breadcrumbs' => ['Projects' => '/projects', $project['project_code'] => null],
            'project' => $project, 'inv' => ShareService::inventory($id),
            'packages' => ShareService::packages($id), 'editPkg' => $editPkg && (int) $editPkg['project_id'] === $id ? $editPkg : null,
            'shareTypes' => MasterDataService::options('share_type'),
            'ledger' => can('shares.view') ? ShareService::ledgerFor($id, 15) : [],
            'galleryCount' => (int) Database::value("SELECT COUNT(*) FROM gallery_items WHERE project_id = ? AND company_id = ? AND status = 'active'", [$id, Tenant::id()]),
        ]);
    }

    public function edit(int $id): never
    {
        $this->authorize('projects.edit');
        $this->form(ProjectService::find($id));
    }

    public function update(int $id): never
    {
        $this->authorize('projects.edit');
        ProjectService::save(Request::post(), $id);
        $this->success('Project updated.', '/projects/' . $id);
    }

    public function destroy(int $id): never
    {
        $this->authorize('projects.delete');
        ProjectService::delete($id);
        $this->success('Project deleted.', '/projects');
    }

    /* ---------- share packages ---------- */

    public function storePackage(int $id): never
    {
        $this->authorize('shares.create');
        ShareService::savePackage($id, Request::post());
        $this->success('Package added.', '/projects/' . $id . '#packages');
    }

    public function updatePackage(int $id, int $pkg): never
    {
        $this->authorize('shares.edit');
        ShareService::savePackage($id, Request::post(), $pkg);
        $this->success('Package updated.', '/projects/' . $id . '#packages');
    }

    public function destroyPackage(int $id, int $pkg): never
    {
        $this->authorize('shares.delete');
        $projectId = ShareService::deletePackage($pkg);
        $this->success('Package removed.', '/projects/' . $projectId . '#packages');
    }

    /* ---------- shares overview ---------- */

    public function shares(): never
    {
        $this->authorize('shares.view');
        render('projects/shares', ['title' => 'Share Inventory', 'breadcrumbs' => ['Projects' => '/projects', 'Shares' => null], 'rows' => ShareService::inventoryAll()]);
    }

    /** AJAX: availability + pricing + packages for sale/offer forms. */
    public function apiShares(int $id): never
    {
        if (!can('sales.create') && !can('offers.create') && !can('shares.view')) {
            $this->authorize('shares.view');
        }
        $inv = ShareService::inventory($id);
        $inv['packages'] = array_map(static fn($p) => ['id' => $p['id'], 'name' => $p['name'], 'shares_count' => $p['shares_count'], 'price' => $p['price']], ShareService::packages($id, true));
        Response::json($inv);
    }

    private function form(?array $project): never
    {
        render('projects/form', [
            'title' => $project ? 'Edit Project' : 'New Project',
            'breadcrumbs' => ['Projects' => '/projects', ($project ? 'Edit' : 'New') => null],
            'project' => $project, 'statuses' => MasterDataService::options('project_status'),
        ]);
    }
}
