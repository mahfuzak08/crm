<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Request;
use Throwable;

final class AuditLogService
{
    /** Never written to the audit log. */
    private const SENSITIVE = ['password', 'password_confirmation', 'current_password', 'token', 'token_hash', '_token', 'session_version', 'nid_front', 'nid_back', 'nid', 'passport', 'date_of_birth', 'photo'];

    public static function log(
        string $action,
        string $module,
        ?int $recordId = null,
        ?array $old = null,
        ?array $new = null,
        ?int $companyId = null
    ): void {
        try {
            [$old, $new] = self::diff($old, $new);
            Database::insert('audit_logs', [
                'company_id' => $companyId ?? (isset($_SESSION['cid']) ? (int) $_SESSION['cid'] : null),
                'user_id'    => AuthService::id(),
                'action'     => strtoupper(mb_substr($action, 0, 50)),
                'module'     => mb_substr($module, 0, 50),
                'record_id'  => $recordId,
                'old_values' => $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
                'new_values' => $new ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
                'ip'         => PHP_SAPI === 'cli' ? 'cli' : Request::ip(),
                'user_agent' => PHP_SAPI === 'cli' ? 'cli' : Request::userAgent(),
            ]);
        } catch (Throwable $e) {
            // Auditing must never break the business action, but failures are logged.
            Logger::error('Audit log failed: ' . $e->getMessage(), ['action' => $action]);
        }
    }

    /** Strip sensitive keys; for updates keep only changed fields. */
    private static function diff(?array $old, ?array $new): array
    {
        $clean = static function (?array $a): ?array {
            if ($a === null) {
                return null;
            }
            foreach (self::SENSITIVE as $k) {
                unset($a[$k]);
            }
            unset($a['created_at'], $a['updated_at']);
            return $a;
        };
        $old = $clean($old);
        $new = $clean($new);
        if ($old !== null && $new !== null) {
            $changedOld = $changedNew = [];
            foreach ($new as $k => $v) {
                if (!array_key_exists($k, $old) || (string) $old[$k] !== (string) $v) {
                    $changedOld[$k] = $old[$k] ?? null;
                    $changedNew[$k] = $v;
                }
            }
            return [$changedOld ?: null, $changedNew ?: null];
        }
        return [$old, $new];
    }
}
