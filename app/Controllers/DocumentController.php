<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ActivityService;
use App\Services\AuditLogService;
use App\Services\DocumentService;
use App\Services\FileUploadService;
use App\Services\MasterDataService;
use App\Services\ProjectService;

final class DocumentController extends Controller
{
    public function index(): never
    {
        $this->authorize('documents.view');
        $f = Request::filters(['q', 'type', 'project', 'customer', 'lead']);
        $this->view('documents/index', [
            'title' => 'Documents', 'breadcrumbs' => ['CRM' => null, 'Documents' => null], 'f' => $f,
            'p' => DocumentService::query($f)->paginate($this->page()),
            'types' => MasterDataService::options('document_type'), 'projects' => ProjectService::options(),
        ], 'documents/_list');
    }

    public function store(): never
    {
        $this->authorize('documents.upload');
        $in = Request::post();
        $id = DocumentService::upload($in, Request::file('file'));
        $doc = DocumentService::find($id);
        if ($doc['lead_id']) {
            ActivityService::system('lead', (int) $doc['lead_id'], 'document_shared', 'Document uploaded: ' . $doc['title'], $id);
        } elseif ($doc['customer_id']) {
            ActivityService::system('customer', (int) $doc['customer_id'], 'document_shared', 'Document uploaded: ' . $doc['title'], $id);
        }
        flash('success', 'Document uploaded.');
        Response::back('/documents');
    }

    /**
     * Secure download: auth (route middleware) -> permission -> company ownership
     * -> linked record visibility -> stream with safe headers.
     */
    public function download(int $id): never
    {
        $this->authorize('documents.view');
        $doc = DocumentService::find($id);
        AuditLogService::log('DOWNLOAD_DOCUMENT', 'documents', $id);
        $inline = str_starts_with($doc['mime'], 'image/') || $doc['mime'] === 'application/pdf';
        Response::file(FileUploadService::absolute($doc['stored_name']), $doc['original_name'], $doc['mime'], $inline && Request::str('dl') !== '1');
    }

    public function share(int $id): never
    {
        $this->authorize('documents.share');
        $doc = DocumentService::toggleShareable($id);
        flash('success', $doc['is_shareable_new'] ? 'Document can now be shared by link.' : 'Sharing disabled; existing links stop working.');
        Response::back('/documents');
    }

    public function destroy(int $id): never
    {
        $this->authorize('documents.delete');
        DocumentService::delete($id);
        flash('success', 'Document deleted.');
        Response::back('/documents');
    }
}
