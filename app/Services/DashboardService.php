<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/** Company-aware dashboard figures (Super Admin may aggregate all companies). */
final class DashboardService
{
    public static function data(array $cids): array
    {
        $in = Database::placeholders($cids);
        $v = static fn(string $sql, array $extra = []) => Database::value($sql, [...$cids, ...$extra]) ?? 0;
        $monthStart = date('Y-m-01');

        $cards = [
            'leads'         => (int) $v("SELECT COUNT(*) FROM leads WHERE company_id IN ($in) AND deleted_at IS NULL"),
            'new_leads'     => (int) $v("SELECT COUNT(*) FROM leads WHERE company_id IN ($in) AND deleted_at IS NULL AND created_at >= ?", [$monthStart]),
            'customers'     => (int) $v("SELECT COUNT(*) FROM customers WHERE company_id IN ($in) AND deleted_at IS NULL"),
            'projects'      => (int) $v("SELECT COUNT(*) FROM projects WHERE company_id IN ($in) AND deleted_at IS NULL"),
            'total_shares'  => (int) $v("SELECT COALESCE(SUM(total_shares),0) FROM projects WHERE company_id IN ($in) AND deleted_at IS NULL"),
            'sold_shares'   => (int) $v("SELECT COALESCE(SUM(s.shares),0) FROM share_sales s JOIN projects p ON p.id = s.project_id AND p.deleted_at IS NULL WHERE s.company_id IN ($in) AND s.status = 'confirmed'"),
            'reserved'      => (int) $v("SELECT COALESCE(SUM(s.shares),0) FROM share_sales s JOIN projects p ON p.id = s.project_id AND p.deleted_at IS NULL WHERE s.company_id IN ($in) AND s.status = 'reserved'"),
            'sales'         => (string) $v("SELECT COALESCE(SUM(grand_total),0) FROM share_sales WHERE company_id IN ($in) AND status = 'confirmed'"),
            'invoiced'      => (string) $v("SELECT COALESCE(SUM(grand_total),0) FROM invoices WHERE company_id IN ($in) AND status NOT IN ('draft','cancelled') AND deleted_at IS NULL"),
            'collection'    => (string) $v("SELECT COALESCE(SUM(amount),0) FROM payments WHERE company_id IN ($in) AND status = 'confirmed'"),
            'due'           => (string) $v("SELECT COALESCE(SUM(due_amount),0) FROM invoices WHERE company_id IN ($in) AND status IN ('unpaid','partial','overdue') AND deleted_at IS NULL"),
            'expense'       => (string) $v("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE company_id IN ($in) AND status = 'approved' AND deleted_at IS NULL"),
        ];
        $board = AuthService::can('directors.view') ? DirectorService::all($cids, true) : [];
        $cards['directors'] = count($board);
        $cards['available_shares'] = max(0, $cards['total_shares'] - $cards['sold_shares'] - $cards['reserved']);

        // Last 12 months series
        $from = date('Y-m-01', strtotime('-11 months'));
        $months = ReportService::monthsSql($from, today());
        $series = Database::fetchAll(
            "SELECT m.ym,
                COALESCE((SELECT SUM(grand_total) FROM share_sales WHERE company_id IN ($in) AND status = 'confirmed' AND sale_date >= ? AND DATE_FORMAT(sale_date, '%Y-%m') = m.ym), 0) AS sales,
                COALESCE((SELECT SUM(amount) FROM payments WHERE company_id IN ($in) AND status = 'confirmed' AND payment_date >= ? AND DATE_FORMAT(payment_date, '%Y-%m') = m.ym), 0) AS collection,
                COALESCE((SELECT SUM(amount) FROM expenses WHERE company_id IN ($in) AND status = 'approved' AND deleted_at IS NULL AND expense_date >= ? AND DATE_FORMAT(expense_date, '%Y-%m') = m.ym), 0) AS expense
             FROM ($months) m ORDER BY m.ym",
            [...$cids, $from, ...$cids, $from, ...$cids, $from]
        );

        $funnel = Database::fetchAll(
            "SELECT s.name, s.color, COUNT(l.id) AS cnt FROM master_data s
             LEFT JOIN leads l ON l.status_id = s.id AND l.deleted_at IS NULL
             WHERE s.company_id IN ($in) AND s.type = 'lead_status' AND s.is_active = 1
             GROUP BY s.name, s.color, s.sort_order ORDER BY MIN(s.sort_order)",
            $cids
        );
        $converted = (int) $v("SELECT COUNT(*) FROM leads WHERE company_id IN ($in) AND deleted_at IS NULL AND customer_id IS NOT NULL");
        $cards['conversion_rate'] = $cards['leads'] > 0 ? round($converted / $cards['leads'] * 100, 1) : 0;

        return compact('cards', 'series', 'funnel', 'board');
    }

    /** Personal work lists for the current user (single company). */
    public static function lists(): array
    {
        $cid = Tenant::id();
        $uid = (int) AuthService::id();
        $all = AuthService::can('leads.view_all');
        $out = [];
        if (AuthService::can('leads.view')) {
            $out['followups'] = Database::fetchAll(
                "SELECT l.id, l.name, l.phone, l.follow_up_date, s.name AS status, s.color FROM leads l JOIN master_data s ON s.id = l.status_id
                 WHERE l.company_id = ? AND l.deleted_at IS NULL AND l.follow_up_date <= CURDATE() AND s.code NOT IN ('converted', 'lost')"
                . ($all ? '' : ' AND (l.assigned_to = ? OR l.created_by = ?)') . ' ORDER BY l.follow_up_date LIMIT 8',
                $all ? [$cid] : [$cid, $uid, $uid]
            );
        }
        if (AuthService::can('invoices.view')) {
            InvoiceService::refreshOverdue();
            $out['overdue'] = Database::fetchAll(
                "SELECT i.id, i.invoice_number, i.due_date, i.due_amount, c.name AS customer FROM invoices i JOIN customers c ON c.id = i.customer_id
                 WHERE i.company_id = ? AND i.status = 'overdue' AND i.deleted_at IS NULL ORDER BY i.due_date LIMIT 8",
                [$cid]
            );
        }
        if (AuthService::can('payments.view')) {
            $out['payments'] = Database::fetchAll(
                "SELECT p.id, p.payment_number, p.payment_date, p.amount, c.name AS customer, m.name AS method FROM payments p
                 JOIN customers c ON c.id = p.customer_id JOIN master_data m ON m.id = p.payment_method_id
                 WHERE p.company_id = ? AND p.status = 'confirmed' ORDER BY p.id DESC LIMIT 8",
                [$cid]
            );
        }
        $out['notifications'] = Database::fetchAll(
            "SELECT id, title, link, created_at FROM notifications WHERE company_id = ? AND user_id = ? AND channel = 'system' AND read_at IS NULL ORDER BY id DESC LIMIT 6",
            [$cid, $uid]
        );
        return $out;
    }
}
