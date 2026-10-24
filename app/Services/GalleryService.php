<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Core\ValidationException;
use App\Core\Validator;

/**
 * Project gallery: images & documents (uploaded privately) and videos.
 * Videos are stored as YouTube/Vimeo links (safe embeds) or small MP4/WEBM uploads.
 */
final class GalleryService
{
    public static function items(int $projectId, bool $activeOnly = false, ?int $companyId = null): array
    {
        return Database::fetchAll(
            'SELECT * FROM gallery_items WHERE project_id = ? AND company_id = ?' . ($activeOnly ? " AND status = 'active'" : '') . ' ORDER BY sort_order, id',
            [$projectId, $companyId ?? Tenant::id()]
        );
    }

    public static function find(int $id): array
    {
        return Database::fetch('SELECT * FROM gallery_items WHERE id = ? AND company_id = ?', [$id, Tenant::id()]) ?? throw new \App\Core\HttpException(404);
    }

    public static function save(int $projectId, array $input, ?int $id = null): int
    {
        ProjectService::find($projectId);
        $old = $id ? self::find($id) : null;
        $data = Validator::make($input, [
            'title'       => 'required|max:200',
            'description' => 'nullable|max:1000',
            'type'        => 'required|in:image,video,document',
            'video_url'   => 'nullable|url|max:500',
            'sort_order'  => 'nullable|integer',
            'status'      => 'nullable|in:active,inactive',
        ]);
        $data['sort_order'] ??= (int) Database::value('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM gallery_items WHERE project_id = ?', [$projectId]);
        $data['status'] ??= 'active';

        $file = Request::file('file');
        if ($data['type'] === 'video') {
            if ($data['video_url']) {
                if (!self::embedUrl($data['video_url'])) {
                    throw new ValidationException(['video_url' => 'Only YouTube or Vimeo links are supported (or upload an MP4/WEBM file).']);
                }
            } elseif (!$file && !$old) {
                throw new ValidationException(['video_url' => 'Provide a YouTube/Vimeo link or upload a video file.']);
            }
        } else {
            $data['video_url'] = null;
            if (!$file && !$old) {
                throw new ValidationException(['file' => 'Please choose a file.']);
            }
        }
        if ($file) {
            $cats = ['image' => ['image'], 'document' => ['document'], 'video' => ['video']][$data['type']];
            $stored = FileUploadService::store($file, $cats, 'file', $data['type'] === 'video' ? 50 : null);
            $data += ['stored_name' => $stored['stored_name'], 'original_name' => $stored['original_name'], 'mime' => $stored['mime'], 'size' => $stored['size']];
            if ($data['type'] === 'video') {
                $data['video_url'] = null;
            }
        }

        if ($id) {
            Database::update('gallery_items', $data, 'id = ? AND company_id = ?', [$id, Tenant::id()]);
            if (isset($data['stored_name']) && $old['stored_name']) {
                FileUploadService::delete($old['stored_name']);
            }
            AuditLogService::log('UPDATE_GALLERY_ITEM', 'gallery', $id, $old, $data);
            return $id;
        }
        $data += ['company_id' => Tenant::id(), 'project_id' => $projectId, 'created_by' => AuthService::id()];
        $newId = Database::insert('gallery_items', $data);
        AuditLogService::log('CREATE_GALLERY_ITEM', 'gallery', $newId, null, $data);
        return $newId;
    }

    public static function delete(int $id): int
    {
        $item = self::find($id);
        Database::run('DELETE FROM gallery_items WHERE id = ? AND company_id = ?', [$id, Tenant::id()]);
        FileUploadService::delete($item['stored_name']);
        AuditLogService::log('DELETE_GALLERY_ITEM', 'gallery', $id, $item, null);
        return (int) $item['project_id'];
    }

    /** Convert a YouTube/Vimeo URL into a safe embed URL, or null if unsupported. */
    public static function embedUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        if (preg_match('~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_\-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[1];
        }
        if (preg_match('~^https?://(?:www\.)?vimeo\.com/(\d{5,12})~', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }
        return null;
    }

    public static function youtubeThumb(?string $url): ?string
    {
        return $url && preg_match('~/embed/([A-Za-z0-9_\-]{11})~', (string) self::embedUrl($url), $m) ? 'https://img.youtube.com/vi/' . $m[1] . '/hqdefault.jpg' : null;
    }
}
