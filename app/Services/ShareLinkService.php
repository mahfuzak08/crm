<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Core\HttpException;

/**
 * HMAC-signed, expiring public links (project gallery, shareable documents,
 * offers) so leads/customers can view content without an account.
 * URL: /share/{kind}/{id}/{expires}/{signature}
 */
final class ShareLinkService
{
    public const KINDS = ['project', 'document', 'offer', 'media'];

    private static function key(): string
    {
        $k = (string) Env::get('APP_KEY', '');
        if ($k === '') {
            // Fallback keeps links working but APP_KEY should always be set.
            $k = hash('sha256', (string) config('db.password') . (string) config('db.database') . BASE_PATH);
        }
        return $k;
    }

    public static function sign(string $kind, int $id, int $expires): string
    {
        return substr(hash_hmac('sha256', $kind . '|' . $id . '|' . $expires, self::key()), 0, 40);
    }

    public static function url(string $kind, int $id, int $days = 30): string
    {
        $expires = strtotime('+' . max(1, $days) . ' days 23:59:59');
        return full_url('/share/' . $kind . '/' . $id . '/' . $expires . '/' . self::sign($kind, $id, $expires));
    }

    /** Relative URL variant for embedding inside an already-validated share page. */
    public static function path(string $kind, int $id, int $expires): string
    {
        return url('/share/' . $kind . '/' . $id . '/' . $expires . '/' . self::sign($kind, $id, $expires));
    }

    public static function verify(string $kind, int $id, int $expires, string $sig): void
    {
        if (!in_array($kind, self::KINDS, true) || $expires < time() || !hash_equals(self::sign($kind, $id, $expires), $sig)) {
            throw new HttpException(404, 'This link is invalid or has expired.');
        }
    }
}
