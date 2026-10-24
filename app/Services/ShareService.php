<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\ValidationException;
use App\Core\Validator;

/**
 * Share inventory & packages.
 * Inventory is derived from share_sales (authoritative) — never stored as a counter:
 *   available = total_shares − sold(confirmed) − reserved
 * Every movement is also written to the immutable share_transactions ledger.
 */
final class ShareService
{
    /** Sold + reserved shares. With $lock the project row is locked FOR UPDATE (call inside a transaction). */
    public static function committed(int $projectId, bool $lock = false): int
    {
        if ($lock) {
            Database::fetch('SELECT id FROM projects WHERE id = ? AND company_id = ? FOR UPDATE', [$projectId, Tenant::id()]);
        }
        return (int) Database::value(
            "SELECT COALESCE(SUM(shares), 0) FROM share_sales WHERE project_id = ? AND company_id = ? AND status IN ('confirmed', 'reserved')" . (Database::inTransaction() ? ' LOCK IN SHARE MODE' : ''),
            [$projectId, Tenant::id()]
        );
    }

    public static function inventory(int $projectId): array
    {
        $p = Database::fetch('SELECT id, total_shares, share_price, registration_fee, development_fee, share_unit FROM projects WHERE id = ? AND company_id = ? AND deleted_at IS NULL', [$projectId, Tenant::id()]);
        if (!$p) {
            throw new HttpException(404);
        }
        $r = Database::fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN status = 'confirmed' THEN shares END), 0) AS sold,
                COALESCE(SUM(CASE WHEN status = 'reserved' THEN shares END), 0) AS reserved,
                COALESCE(SUM(CASE WHEN status = 'cancelled' THEN shares END), 0) AS cancelled
             FROM share_sales WHERE project_id = ? AND company_id = ?",
            [$projectId, Tenant::id()]
        );
        $transferred = (int) Database::value("SELECT COALESCE(SUM(shares), 0) FROM share_transactions WHERE project_id = ? AND company_id = ? AND type = 'transfer_out'", [$projectId, Tenant::id()]);
        $total = (int) $p['total_shares'];
        return [
            'total' => $total,
            'sold' => (int) $r['sold'],
            'reserved' => (int) $r['reserved'],
            'cancelled' => (int) $r['cancelled'],
            'transferred' => $transferred,
            'available' => max(0, $total - (int) $r['sold'] - (int) $r['reserved']),
            'share_price' => $p['share_price'],
            'registration_fee' => $p['registration_fee'],
            'development_fee' => $p['development_fee'],
            'share_unit' => $p['share_unit'],
        ];
    }

    /** Lock project and assert enough shares are available. */
    public static function assertAvailable(int $projectId, int $shares): void
    {
        $total = (int) Database::value('SELECT total_shares FROM projects WHERE id = ? AND company_id = ? AND deleted_at IS NULL FOR UPDATE', [$projectId, Tenant::id()]);
        $available = $total - self::committed($projectId);
        if ($shares > $available) {
            throw new ValidationException(['quantity' => "Only $available shares are available in this project; $shares requested."]);
        }
    }

    public static function ledger(int $projectId, string $type, int $shares, ?int $saleId = null, ?int $customerId = null, ?string $notes = null): void
    {
        Database::insert('share_transactions', [
            'company_id' => Tenant::id(), 'project_id' => $projectId, 'sale_id' => $saleId, 'customer_id' => $customerId,
            'type' => $type, 'shares' => $shares, 'notes' => $notes, 'created_by' => AuthService::id(),
        ]);
    }

    /* ---------------- packages ---------------- */

    public static function packages(int $projectId, bool $activeOnly = false): array
    {
        return Database::fetchAll(
            'SELECT sp.*, st.name AS share_type FROM share_packages sp LEFT JOIN master_data st ON st.id = sp.share_type_id
             WHERE sp.project_id = ? AND sp.company_id = ? AND sp.deleted_at IS NULL' . ($activeOnly ? " AND sp.status = 'active'" : '') . ' ORDER BY sp.shares_count, sp.name',
            [$projectId, Tenant::id()]
        );
    }

    public static function findPackage(int $id): array
    {
        return Tenant::findOrFail('share_packages', $id);
    }

    public static function savePackage(int $projectId, array $input, ?int $id = null): int
    {
        ProjectService::find($projectId);
        $old = $id ? self::findPackage($id) : null;
        if ($old && (int) $old['project_id'] !== $projectId) {
            throw new HttpException(404);
        }
        $data = Validator::make($input, [
            'name'          => 'required|max:120',
            'share_type_id' => ['nullable', MasterDataService::rule('share_type')],
            'shares_count'  => 'required|integer|gte:1|lte:1000000',
            'price'         => 'required|money',
            'description'   => 'nullable|max:500',
            'status'        => 'required|in:active,inactive',
        ], ['shares_count' => 'Shares per package', 'price' => 'Package price', 'share_type_id' => 'Share type']);

        if ($id) {
            Database::update('share_packages', $data, 'id = ? AND company_id = ?', [$id, Tenant::id()]);
            AuditLogService::log('UPDATE_SHARE_PACKAGE', 'share_packages', $id, $old, $data);
            return $id;
        }
        $data += ['company_id' => Tenant::id(), 'project_id' => $projectId];
        $newId = Database::insert('share_packages', $data);
        AuditLogService::log('CREATE_SHARE_PACKAGE', 'share_packages', $newId, null, $data);
        return $newId;
    }

    public static function deletePackage(int $id): int
    {
        $pkg = self::findPackage($id);
        Database::run('UPDATE share_packages SET deleted_at = NOW(), status = \'inactive\' WHERE id = ? AND company_id = ?', [$id, Tenant::id()]);
        AuditLogService::log('DELETE_SHARE_PACKAGE', 'share_packages', $id, $pkg, null);
        return (int) $pkg['project_id'];
    }

    /** Overall inventory for every project (shares page / report). */
    public static function inventoryAll(): array
    {
        return Database::fetchAll(
            "SELECT p.id, p.project_code, p.project_name, p.total_shares, p.share_price, p.share_unit,
                COALESCE(SUM(CASE WHEN ss.status = 'confirmed' THEN ss.shares END), 0) AS sold,
                COALESCE(SUM(CASE WHEN ss.status = 'reserved' THEN ss.shares END), 0) AS reserved,
                COALESCE(SUM(CASE WHEN ss.status = 'cancelled' THEN ss.shares END), 0) AS cancelled,
                COALESCE(SUM(CASE WHEN ss.status = 'confirmed' THEN ss.grand_total END), 0) AS sales_value
             FROM projects p LEFT JOIN share_sales ss ON ss.project_id = p.id
             WHERE p.company_id = ? AND p.deleted_at IS NULL
             GROUP BY p.id ORDER BY p.project_name",
            [Tenant::id()]
        );
    }

    public static function ledgerFor(int $projectId, int $limit = 50): array
    {
        return Database::fetchAll(
            'SELECT st.*, c.name AS customer_name, ss.sale_number, u.name AS user_name FROM share_transactions st
             LEFT JOIN customers c ON c.id = st.customer_id LEFT JOIN share_sales ss ON ss.id = st.sale_id LEFT JOIN users u ON u.id = st.created_by
             WHERE st.project_id = ? AND st.company_id = ? ORDER BY st.id DESC LIMIT ' . $limit,
            [$projectId, Tenant::id()]
        );
    }
}
