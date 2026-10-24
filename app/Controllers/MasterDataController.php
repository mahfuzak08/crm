<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Services\MasterDataService;
use App\Services\Tenant;

final class MasterDataController extends Controller
{
    public function index(): never
    {
        $this->authorize('master_data.manage');
        $counts = Database::pairs('SELECT type, COUNT(*) FROM master_data WHERE company_id = ? GROUP BY type', [Tenant::id()]);
        render('master_data/index', ['title' => 'Master Data', 'breadcrumbs' => ['Settings' => null, 'Master Data' => null], 'types' => MasterDataService::TYPES, 'counts' => $counts]);
    }

    public function type(string $type): never
    {
        $this->authorize('master_data.manage');
        MasterDataService::assertType($type);
        $editId = Request::int('edit');
        $items = MasterDataService::all($type);
        $editing = $editId ? (array_values(array_filter($items, static fn($i) => (int) $i['id'] === $editId))[0] ?? null) : null;
        [$label, $singular, $hasColor] = MasterDataService::TYPES[$type];
        render('master_data/type', [
            'title' => $label, 'breadcrumbs' => ['Master Data' => '/master-data', $label => null],
            'type' => $type, 'singular' => $singular, 'hasColor' => $hasColor, 'items' => $items, 'editing' => $editing,
        ]);
    }

    public function store(string $type): never
    {
        $this->authorize('master_data.manage');
        MasterDataService::save($type, Request::post());
        $this->success('Saved.', '/master-data/' . $type);
    }

    public function update(string $type, int $id): never
    {
        $this->authorize('master_data.manage');
        MasterDataService::save($type, Request::post(), $id);
        $this->success('Updated.', '/master-data/' . $type);
    }

    public function destroy(string $type, int $id): never
    {
        $this->authorize('master_data.manage');
        MasterDataService::delete($type, $id);
        $this->success('Deleted.', '/master-data/' . $type);
    }
}
