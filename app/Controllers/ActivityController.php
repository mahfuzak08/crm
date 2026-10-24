<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\ValidationException;
use App\Services\ActivityService;
use App\Services\MasterDataService;
use App\Services\UserService;

final class ActivityController extends Controller
{
    public function index(): never
    {
        $this->authorize('activities.view');
        $f = Request::filters(['q', 'type', 'user', 'from', 'to']);
        $this->view('activities/index', [
            'title' => 'Activities', 'breadcrumbs' => ['CRM' => null, 'Activities' => null], 'f' => $f,
            'p' => ActivityService::query($f)->paginate($this->page()),
            'types' => MasterDataService::options('activity_type'), 'users' => UserService::options(),
        ], 'activities/_list');
    }

    public function store(): never
    {
        $this->authorize('activities.create');
        $subject = Request::str('subject');
        if (!in_array($subject, ['lead', 'customer'], true)) {
            throw ValidationException::rule('Invalid activity subject.');
        }
        $id = Request::int('subject_id');
        ActivityService::create($subject, $id, Request::post());
        $this->success('Activity logged.', '/' . $subject . 's/' . $id);
    }

    public function destroy(int $id): never
    {
        $this->authorize('activities.delete');
        ActivityService::delete($id);
        Response::back('/activities');
    }
}
