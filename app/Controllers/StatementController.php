<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Money;
use App\Core\Request;
use App\Services\CustomerService;
use App\Services\ProjectService;
use App\Services\Tenant;

/** Customer account statement: opening balance + invoices (debit) + payments (credit). */
final class StatementController extends Controller
{
    public function index(): never
    {
        $this->authorize('customers.statement');
        $customerId = Request::int('customer');
        $projectId = Request::int('project');
        $from = Request::str('from');
        $to = Request::str('to');
        $from = is_valid_date($from) ? $from : date('Y-01-01');
        $to = is_valid_date($to) ? $to : today();

        $data = ['title' => 'Customer Statement', 'breadcrumbs' => ['Sales' => null, 'Statement' => null],
            'customers' => CustomerService::options($customerId ?: null), 'projects' => ProjectService::options(),
            'f' => ['customer' => $customerId, 'project' => $projectId, 'from' => $from, 'to' => $to], 'customer' => null];

        if ($customerId) {
            $customer = CustomerService::find($customerId);
            [$opening, $rows, $totals] = $this->build($customerId, $projectId, $from, $to);
            $data = array_merge($data, compact('customer', 'opening', 'rows', 'totals'));
            if (Request::str('print') === '1') {
                render('statements/print', $data + ['company' => Tenant::company(), 'docTitle' => 'Statement of Account'], 'layouts/print');
            }
        }
        render('statements/index', $data);
    }

    private function build(int $customerId, int $projectId, string $from, string $to): array
    {
        $cid = Tenant::id();
        $pSql = $projectId ? ' AND project_id = ?' : '';
        $pParam = $projectId ? [$projectId] : [];

        $invBefore = (string) Database::value("SELECT COALESCE(SUM(grand_total), 0) FROM invoices WHERE company_id = ? AND customer_id = ? AND status NOT IN ('draft', 'cancelled') AND deleted_at IS NULL AND invoice_date < ?$pSql", [$cid, $customerId, $from, ...$pParam]);
        $payBefore = (string) Database::value("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE company_id = ? AND customer_id = ? AND status = 'confirmed' AND payment_date < ?$pSql", [$cid, $customerId, $from, ...$pParam]);
        $opening = Money::toCents($invBefore) - Money::toCents($payBefore);

        $lines = Database::fetchAll(
            "SELECT * FROM (
                SELECT invoice_date AS d, 'invoice' AS type, id, invoice_number AS ref, CONCAT('Invoice', IF(notes IS NULL, '', CONCAT(' — ', notes))) AS description, grand_total AS debit, 0 AS credit, created_at
                FROM invoices WHERE company_id = ? AND customer_id = ? AND status NOT IN ('draft', 'cancelled') AND deleted_at IS NULL AND invoice_date BETWEEN ? AND ?$pSql
                UNION ALL
                SELECT p.payment_date, 'payment', p.id, p.payment_number, CONCAT('Payment (', m.name, IF(p.reference IS NULL, '', CONCAT(' · ', p.reference)), ')'), 0, p.amount, p.created_at
                FROM payments p JOIN master_data m ON m.id = p.payment_method_id
                WHERE p.company_id = ? AND p.customer_id = ? AND p.status = 'confirmed' AND p.payment_date BETWEEN ? AND ?" . ($projectId ? ' AND p.project_id = ?' : '') . "
             ) x ORDER BY d, created_at",
            [$cid, $customerId, $from, $to, ...$pParam, $cid, $customerId, $from, $to, ...$pParam]
        );
        $bal = $opening;
        $dr = $cr = 0;
        foreach ($lines as &$l) {
            $d = Money::toCents((string) $l['debit']);
            $c = Money::toCents((string) $l['credit']);
            $dr += $d;
            $cr += $c;
            $bal += $d - $c;
            $l['balance'] = Money::fromCents($bal);
        }
        unset($l);
        return [Money::fromCents($opening), $lines, ['debit' => Money::fromCents($dr), 'credit' => Money::fromCents($cr), 'closing' => Money::fromCents($bal)]];
    }
}
