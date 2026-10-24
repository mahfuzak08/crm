<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\FileUploadService;
use App\Services\GalleryService;
use App\Services\ProjectService;
use App\Services\ShareLinkService;

final class GalleryController extends Controller
{
    public function index(): never
    {
        $this->authorize('gallery.view');
        $projects = ProjectService::options();
        $projectId = Request::int('project') ?: (int) array_key_first($projects);
        $project = $projectId ? ProjectService::find($projectId) : null;
        $editing = Request::int('edit') ? GalleryService::find(Request::int('edit')) : null;
        render('gallery/index', [
            'title' => 'Project Gallery', 'breadcrumbs' => ['Projects' => '/projects', 'Gallery' => null],
            'projects' => $projects, 'project' => $project,
            'items' => $project ? GalleryService::items($projectId) : [],
            'editing' => $editing && $project && (int) $editing['project_id'] === $projectId ? $editing : null,
            'shareUrl' => $project ? ShareLinkService::url('project', $projectId) : null,
        ]);
    }

    public function store(int $project): never
    {
        $this->authorize('gallery.manage');
        GalleryService::save($project, Request::post());
        $this->success('Gallery item added.', '/gallery?project=' . $project);
    }

    public function update(int $project, int $id): never
    {
        $this->authorize('gallery.manage');
        GalleryService::save($project, Request::post(), $id);
        $this->success('Gallery item updated.', '/gallery?project=' . $project);
    }

    public function destroy(int $id): never
    {
        $this->authorize('gallery.manage');
        $projectId = GalleryService::delete($id);
        $this->success('Gallery item deleted.', '/gallery?project=' . $projectId);
    }

    public function file(int $id): never
    {
        $this->authorize('gallery.view');
        $item = GalleryService::find($id);
        if (!$item['stored_name']) {
            throw new HttpException(404);
        }
        Response::file(FileUploadService::absolute($item['stored_name']), (string) $item['original_name'], (string) $item['mime'], $item['type'] !== 'document');
    }
}
