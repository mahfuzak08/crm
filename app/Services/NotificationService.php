<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Services\Notifications\Channel;
use App\Services\Notifications\EmailChannel;
use Throwable;

/**
 * Channel-agnostic notifications. V1 delivers:
 *  - system (in-app, stored in `notifications`)
 *  - email (when MAIL_* configured)
 * SMS / WhatsApp providers can be added by implementing Notifications\Channel.
 * WhatsApp click-to-chat links (wa.me) are generated without any API.
 */
final class NotificationService
{
    /** @return array<string, Channel> */
    public static function channels(): array
    {
        static $channels = null;
        return $channels ??= ['email' => new EmailChannel()];
    }

    public static function canEmail(): bool
    {
        return self::channels()['email']->isConfigured();
    }

    /** Send via a channel and record the attempt. Returns true on success. */
    public static function send(string $channel, string $recipient, string $subject, string $body, array $options = [], ?int $companyId = null): bool
    {
        $ch = self::channels()[$channel] ?? null;
        $cid = $companyId ?? (int) ($_SESSION['cid'] ?? 0);
        $status = 'failed';
        $error = null;
        try {
            if (!$ch) {
                throw new \RuntimeException('Unknown channel');
            }
            $ch->send($recipient, $subject, $body, $options);
            $status = 'sent';
        } catch (Throwable $e) {
            $error = mb_substr($e->getMessage(), 0, 250);
            Logger::error('Notification failed', ['channel' => $channel, 'error' => $error]);
        }
        if ($cid > 0) {
            Database::insert('notifications', [
                'company_id' => $cid, 'user_id' => null, 'channel' => $channel, 'recipient' => mb_substr($recipient, 0, 150),
                'title' => mb_substr($subject, 0, 200), 'body' => null, 'status' => $status, 'error' => $error,
            ]);
        }
        return $status === 'sent';
    }

    /** In-app notification for a user. */
    public static function notifyUser(int $userId, string $title, ?string $body = null, ?string $link = null): void
    {
        Database::insert('notifications', [
            'company_id' => Tenant::id(), 'user_id' => $userId, 'channel' => 'system',
            'title' => mb_substr($title, 0, 200), 'body' => $body, 'link' => $link, 'status' => 'queued',
        ]);
    }

    /** WhatsApp click-to-chat link (no API needed). Bangladeshi local numbers get the 880 prefix. */
    public static function whatsappLink(?string $phone, string $text = ''): ?string
    {
        $d = phone_digits($phone);
        if ($d === '') {
            return null;
        }
        if (str_starts_with($d, '01') && strlen($d) === 11) {
            $d = '88' . $d;
        }
        return 'https://wa.me/' . $d . ($text !== '' ? '?text=' . rawurlencode($text) : '');
    }

    /** Wrap content in a simple branded HTML email. Content must already be escaped. */
    public static function emailLayout(string $title, string $htmlContent, ?array $company = null): string
    {
        $co = $company ?? Tenant::company();
        $brand = preg_match('/^#[0-9a-f]{6}$/i', (string) ($co['theme_color'] ?? '')) ? $co['theme_color'] : '#0f766e';
        return '<!doctype html><html><body style="margin:0;background:#f4f6fa;font-family:Arial,Helvetica,sans-serif;color:#1e2533">'
            . '<div style="max-width:600px;margin:24px auto;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e6e9ef">'
            . '<div style="background:' . $brand . ';color:#fff;padding:16px 24px;font-size:18px;font-weight:bold">' . e($co['name'] ?? '') . '</div>'
            . '<div style="padding:24px"><h2 style="margin-top:0;font-size:18px">' . e($title) . '</h2>' . $htmlContent . '</div>'
            . '<div style="padding:12px 24px;font-size:12px;color:#6b7385;border-top:1px solid #e6e9ef">' . e(trim(($co['address'] ?? '') . ' ' . ($co['phone'] ?? ''))) . '</div>'
            . '</div></body></html>';
    }
}
