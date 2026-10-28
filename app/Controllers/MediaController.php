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

    /** Web app manifest: lets phones install the CRM / open it full-screen like an app (also used by a WebView wrapper). */
    public function manifest(): never
    {
        $co = Database::fetch('SELECT id, name, theme_color, logo FROM companies WHERE deleted_at IS NULL ORDER BY id LIMIT 1') ?? [];
        $brand = preg_match('/^#[0-9a-f]{6}$/i', (string) ($co['theme_color'] ?? '')) ? $co['theme_color'] : '#0f766e';
        $name = (string) ($co['name'] ?? config('name'));
        $icons = [['src' => asset('images/favicon.svg'), 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any']];
        if (!empty($co['logo'])) {
            array_unshift($icons, ['src' => url('/media/logo/' . (int) $co['id']), 'sizes' => '512x512', 'purpose' => 'any']);
        }
        http_response_code(200);
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo json_encode([
            'name' => $name, 'short_name' => mb_substr($name, 0, 20), 'start_url' => url('/'), 'scope' => url('/'),
            'display' => 'standalone', 'orientation' => 'portrait', 'background_color' => '#f4f6fa', 'theme_color' => $brand, 'icons' => $icons,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
