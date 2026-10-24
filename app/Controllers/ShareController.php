<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Services\FileUploadService;
use App\Services\GalleryService;
use App\Services\OfferService;
use App\Services\ShareLinkService;

/**
 * Public, signed & expiring links (no login): project gallery, shareable
 * documents, gallery media and offers. Everything is looked up by id AND
 * verified with an HMAC signature, so ids cannot be enumerated.
 */
final class ShareController extends Controller
{
    public function show(string $kind, int $id, int $expires, string $sig): never
    {
        ShareLinkService::verify($kind, $id, $expires, $sig);
        header('X-Robots-Tag: noindex, nofollow');
        match ($kind) {
            'project'  => $this->project($id, $expires),
            'media'    => $this->media($id),
            'document' => $this->document($id),
            'offer'    => $this->offer($id),
        };
    }

    private function project(int $id, int $expires): never
    {
        $project = Database::fetch('SELECT * FROM projects WHERE id = ? AND deleted_at IS NULL', [$id]) ?? throw new HttpException(404);
        $company = Database::fetch('SELECT * FROM companies WHERE id = ?', [$project['company_id']]);
        $items = GalleryService::items($id, true, (int) $project['company_id']);
        $docs = Database::fetchAll('SELECT id, title, mime, size FROM documents WHERE project_id = ? AND company_id = ? AND is_shareable = 1 AND deleted_at IS NULL ORDER BY id DESC', [$id, $project['company_id']]);
        render('share/project', compact('project', 'company', 'items', 'docs', 'expires') + ['title' => $project['project_name']], null);
    }

    private function media(int $id): never
    {
        $item = Database::fetch("SELECT g.* FROM gallery_items g JOIN projects p ON p.id = g.project_id AND p.deleted_at IS NULL WHERE g.id = ? AND g.status = 'active'", [$id]);
        if (!$item || !$item['stored_name']) {
            throw new HttpException(404);
        }
        Response::file(FileUploadService::absolute($item['stored_name']), (string) $item['original_name'], (string) $item['mime'], $item['type'] !== 'document');
    }

    private function document(int $id): never
    {
        $doc = Database::fetch('SELECT * FROM documents WHERE id = ? AND is_shareable = 1 AND deleted_at IS NULL', [$id]);
        if (!$doc) {
            throw new HttpException(404, 'This document is no longer shared.');
        }
        Response::file(FileUploadService::absolute($doc['stored_name']), $doc['original_name'], $doc['mime'], $doc['mime'] === 'application/pdf' || str_starts_with($doc['mime'], 'image/'));
    }

    private function offer(int $id): never
    {
        $offer = Database::fetch("SELECT * FROM offers WHERE id = ? AND deleted_at IS NULL AND status IN ('sent', 'viewed', 'accepted')", [$id])
            ?? throw new HttpException(404, 'This offer is no longer available.');
        OfferService::markViewed($offer);
        render('offers/print', OfferService::printData($offer) + ['public' => true], null);
    }
}
