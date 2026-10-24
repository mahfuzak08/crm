<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\ActivityService;
use App\Services\DocumentService;
use App\Services\LeadService;
use App\Services\MasterDataService;
use App\Services\NotificationService;
use App\Services\ProjectService;
use App\Services\ShareLinkService;
use App\Services\Tenant;
use App\Services\UserService;

final class LeadController extends Controller
{
    private const FILTERS = ['q', 'status', 'source', 'project', 'assigned', 'followup', 'from', 'to', 'sort', 'dir'];

    public function index(): never
    {
        $this->authorize('leads.view');
        $f = Request::filters(self::FILTERS);
        if (Request::str('export') === 'csv') {
            $this->authorize('reports.export');
            $rows = (static function () use ($f) {
                foreach (LeadService::query($f)->cursor() as $r) {
                    yield [$r['lead_code'], $r['name'], $r['phone'], $r['email'], $r['status_name'], $r['source_name'], $r['project_name'], $r['assigned_name'], $r['follow_up_date'], $r['created_at']];
                }
            })();
            Response::csv('leads-' . date('Ymd') . '.csv', ['Code', 'Name', 'Phone', 'Email', 'Status', 'Source', 'Project', 'Assigned', 'Follow-up', 'Created'], $rows);
        }
        $this->view('leads/index', [
            'title' => 'Leads', 'breadcrumbs' => ['CRM' => null, 'Leads' => null], 'f' => $f,
            'p' => LeadService::query($f)->paginate($this->page()),
            'statuses' => MasterDataService::all('lead_status'), 'sources' => MasterDataService::options('lead_source'),
            'projects' => ProjectService::options(), 'users' => UserService::options(),
        ], 'leads/_list');
    }

    public function create(): never
    {
        $this->authorize('leads.create');
        $this->form(null);
    }

    public function store(): never
    {
        $this->authorize('leads.create');
        $id = LeadService::save(Request::post());
        $this->success('Lead created.', '/leads/' . $id);
    }

    public function show(int $id): never
    {
        $this->authorize('leads.view');
        $lead = LeadService::find($id);
        $projectShare = $lead['project_id'] && can('gallery.view') ? ShareLinkService::url('project', (int) $lead['project_id']) : null;
        $msg = 'Hello ' . $lead['name'] . ', thank you for your interest in ' . (company()['name'] ?? '') . '.';
        if ($projectShare) {
            $msg .= ' View the project gallery: ' . $projectShare;
        }
        render('leads/show', [
            'title' => $lead['name'], 'breadcrumbs' => ['Leads' => '/leads', $lead['lead_code'] => null],
            'lead' => $lead,
            'timeline' => can('activities.view') ? ActivityService::timeline('lead', $id) : [],
            'activityTypes' => MasterDataService::options('activity_type'),
            'statuses' => MasterDataService::options('lead_status'),
            'users' => UserService::options(),
            'documents' => can('documents.view') ? DocumentService::forSubject('lead_id', $id) : [],
            'docTypes' => MasterDataService::options('document_type'),
            'offers' => can('offers.view') ? Database::fetchAll('SELECT id, offer_number, total, status, created_at FROM offers WHERE lead_id = ? AND company_id = ? AND deleted_at IS NULL ORDER BY id DESC', [$id, Tenant::id()]) : [],
            'projectShare' => $projectShare,
            'whatsapp' => NotificationService::whatsappLink($lead['phone'], $msg),
            'canEmail' => NotificationService::canEmail(),
        ]);
    }

    public function edit(int $id): never
    {
        $this->authorize('leads.edit');
        $this->form(LeadService::find($id));
    }

    public function update(int $id): never
    {
        $this->authorize('leads.edit');
        LeadService::save(Request::post(), $id);
        $this->success('Lead updated.', '/leads/' . $id);
    }

    public function status(int $id): never
    {
        $this->authorize('leads.edit');
        LeadService::setStatus($id, Request::int('status_id'));
        $this->success('Status updated.', '/leads/' . $id);
    }

    public function assign(int $id): never
    {
        $this->authorize('leads.assign');
        $uid = Request::int('assigned_to');
        LeadService::assign($id, $uid ?: null);
        $this->success('Lead assigned.', '/leads/' . $id);
    }

    public function convert(int $id): never
    {
        $this->authorize('leads.convert');
        $this->authorize('customers.create');
        $customerId = LeadService::convert($id);
        $this->success('Lead converted to customer.', '/customers/' . $customerId . '/edit');
    }

    public function destroy(int $id): never
    {
        $this->authorize('leads.delete');
        LeadService::delete($id);
        $this->success('Lead deleted.', '/leads');
    }

    /** Log "shared gallery/document via WhatsApp/email" and redirect to the external link. */
    public function logShare(int $id): never
    {
        $this->authorize('leads.view');
        $lead = LeadService::find($id);
        $channel = Request::str('channel') === 'email' ? 'email' : 'whatsapp';
        ActivityService::system('lead', $id, $channel, 'Shared project information via ' . ($channel === 'email' ? 'email' : 'WhatsApp'));
        Response::json(['ok' => true]);
    }

    private function form(?array $lead): never
    {
        render('leads/form', [
            'title' => $lead ? 'Edit Lead' : 'New Lead',
            'breadcrumbs' => ['Leads' => '/leads', ($lead ? $lead['lead_code'] : 'New') => null],
            'lead' => $lead,
            'statuses' => MasterDataService::options('lead_status'), 'sources' => MasterDataService::options('lead_source'),
            'projects' => ProjectService::options(), 'users' => UserService::options(),
        ]);
    }
}
