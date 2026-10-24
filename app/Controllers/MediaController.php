<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Response;
use App\Services\FileUploadService;

/** Non-sensitive public media (company logo used on print/share pages). */
final class MediaController extends Controller
{
    public function logo(int $id): never
    {
        $logo = Database::value('SELECT logo FROM companies WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$logo) {
            throw new HttpException(404);
        }
        $ext = strtolower(pathinfo((string) $logo, PATHINFO_EXTENSION));
        Response::file(FileUploadService::absolute((string) $logo), 'logo.' . $ext, FileUploadService::MIME_BY_EXT[$ext] ?? 'application/octet-stream', true);
    }
}
