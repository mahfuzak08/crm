<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\FileUploadService;
use App\Services\FundService;
use App\Services\MasterDataService;
use App\Services\ProjectService;
use App\Services\Tenant;

final class FundController extends Controller
{
    public function index(): never
    {
        $this->authorize('funds.view');
        $f = Request::filters(['q', 'contributor', 'type', 'status', 'project', 'from', 'to', 'sort', 'dir']);
        if (Request::str('export') === 'csv') {
            $this->authorize('reports.export');
            $rows = (static function () use ($f) {
                foreach (FundService::query($f)->cursor() as $r) {
                    yield [$r['fund_number'], $r['contribution_date'], $r['contributor_name'], $r['contributor_phone'], FundService::TYPES[$r['fund_type']], $r['project_name'], $r['purpose'], $r['method_name'], $r['reference'], $r['amount'], $r['status']];
                }
            })();
            Response::csv('fund-contributions-' . date('Ymd') . '.csv', ['Number', 'Date', 'Contributor', 'Phone', 'Type', 'Project', 'Purpose', 'Method', 'Reference', 'Amount', 'Status'], $rows);
        }
        $this->view('funds/index', [
            'title' => 'Fund Contributions', 'breadcrumbs' => ['Finance' => null, 'Fund Contributions' => null], 'f' => $f,
            'p' => FundService::query($f)->paginate($this->page()),
            'total' => FundService::query($f)->where("x.status = 'received'")->aggregate(['sum' => 'COALESCE(SUM(x.amount),0)'])['sum'] ?? 0,
            'byContributor' => FundService::byContributor($f),
            'contributors' => FundService::contributorOptions(), 'projects' => ProjectService::options(),
        ], 'funds/_list');
    }

    public function create(): never
    {
        $this->authorize('funds.create');
        $this->form(null);
    }

    public function store(): never
    {
        $this->authorize('funds.create');
        $id = FundService::save(Request::post());
        $this->success('Fund contribution recorded.', '/funds/' . $id);
    }

    public function show(int $id): never
    {
        $this->authorize('funds.view');
        $x = FundService::find($id);
        render('funds/show', ['title' => 'Fund ' . $x['fund_number'], 'breadcrumbs' => ['Fund Contributions' => '/funds', $x['fund_number'] => null], 'x' => $x]);
    }

    public function edit(int $id): never
    {
        $this->authorize('funds.edit');
        $this->form(FundService::find($id));
    }

    public function update(int $id): never
    {
        $this->authorize('funds.edit');
        FundService::save(Request::post(), $id);
        $this->success('Fund contribution updated.', '/funds/' . $id);
    }

    public function cancel(int $id): never
    {
        $this->authorize('funds.delete');
        FundService::cancel($id, Request::str('reason') ?: null);
        $this->success('Fund contribution cancelled.', '/funds/' . $id);
    }

    public function attachment(int $id): never
    {
        $this->authorize('funds.view');
        $x = FundService::find($id);
        if (!$x['attachment']) {
            throw new HttpException(404);
        }
        Response::file(FileUploadService::absolute($x['attachment']), (string) $x['attachment_name'], (string) $x['attachment_mime'], true);
    }

    public function receipt(int $id): never
    {
        $this->authorize('funds.view');
        $x = FundService::find($id);
        render('funds/receipt', ['x' => $x, 'company' => Tenant::company(), 'title' => 'Receipt ' . $x['fund_number'], 'docTitle' => 'Fund Received Receipt'], 'layouts/print');
    }

    private function form(?array $x): never
    {
        $contributor = Request::int('contributor');
        render('funds/form', [
            'title' => $x ? 'Edit Fund Contribution' : 'New Fund Contribution',
            'breadcrumbs' => ['Fund Contributions' => '/funds', ($x ? $x['fund_number'] : 'New') => null],
            'x' => $x ?? ['contribution_date' => today(), 'fund_type' => 'capital', 'user_id' => $contributor ?: null], 'isEdit' => (bool) $x,
            'contributors' => FundService::contributorOptions(), 'methods' => MasterDataService::options('payment_method'),
            'projects' => ProjectService::options(),
        ]);
    }
}
