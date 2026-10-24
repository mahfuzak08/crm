<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Money;
use App\Core\Query;

/**
 * Report definitions. Each report returns:
 *   columns: key => [label, type(text|money|int|date|status)]
 *   query:   Query (filtered, company-scoped) for listing/CSV/print
 *   totals:  aggregate row (optional)
 *   summary: small grouped tables (optional)
 */
final class ReportService
{
    public const REPORTS = [
        'sales'      => ['Sales Report', 'Share sales with totals, paid and due', 'cart-check'],
        'collection' => ['Collection Report', 'Payments received by date, method and project', 'cash-coin'],
        'due'        => ['Due Report', 'Outstanding invoices with ageing', 'hourglass-split'],
        'expense'    => ['Expense Report', 'Approved expenses by category', 'wallet2'],
        'leads'      => ['Lead Report', 'Leads by status, source and sales person', 'people'],
        'customers'  => ['Customer Report', 'Customers with shares, invoiced, paid and due', 'person-vcard'],
        'shares'     => ['Share Inventory Report', 'Total, sold, reserved and available shares', 'grid-3x3-gap'],
        'employees'  => ['Employee Report', 'Employees and salary paid in the period', 'person-badge'],
        'financial'  => ['Financial Summary', 'Sales, collection, expenses and net cash flow', 'graph-up-arrow'],
    ];

    /** Module permission required in addition to reports.view. */
    public const REQUIRES = [
        'sales' => 'sales.view', 'collection' => 'payments.view', 'due' => 'invoices.view', 'expense' => 'expenses.view',
        'leads' => 'leads.view', 'customers' => 'customers.view', 'shares' => 'shares.view', 'employees' => 'employees.view',
        'financial' => 'invoices.view',
    ];

    /** Company ids in scope: own company, or (Super Admin) chosen/all companies. */
    public static function companyScope(string $choice): array
    {
        if (!AuthService::isSuperAdmin() || $choice === '') {
            return [Tenant::id()];
        }
        if ($choice === 'all') {
            return array_map('intval', array_column(Tenant::allowedCompanies(), 'id'));
        }
        $ids = array_map('intval', array_column(Tenant::allowedCompanies(), 'id'));
        return in_array((int) $choice, $ids, true) ? [(int) $choice] : [Tenant::id()];
    }

    public static function build(string $type, array $f): array
    {
        if (!isset(self::REPORTS[$type]) || $type === 'financial') {
            throw new HttpException(404);
        }
        $cids = self::companyScope($f['company'] ?? '');
        return match ($type) {
            'sales' => self::sales($f, $cids),
            'collection' => self::collection($f, $cids),
            'due' => self::due($f, $cids),
            'expense' => self::expense($f, $cids),
            'leads' => self::leads($f, $cids),
            'customers' => self::customers($f, $cids),
            'shares' => self::shares($f, $cids),
            'employees' => self::employees($f, $cids),
        };
    }

    private static function sales(array $f, array $cids): array
    {
        $q = Query::table('share_sales s')
            ->select('s.sale_number, s.sale_date, c.name AS customer, p.project_name AS project, sp.name AS package, s.shares, s.subtotal, s.discount,
                      (s.registration_fee + s.development_fee + s.other_fee) AS fees, s.grand_total, s.paid_amount, s.due_amount, u.name AS sales_person, s.status')
            ->join('JOIN customers c ON c.id = s.customer_id')->join('JOIN projects p ON p.id = s.project_id')
            ->join('LEFT JOIN share_packages sp ON sp.id = s.package_id')->join('LEFT JOIN users u ON u.id = s.sales_person_id')
            ->whereIn('s.company_id', $cids)
            ->where("s.status = ?", in_array($f['status'] ?? '', ['confirmed', 'reserved', 'cancelled'], true) ? $f['status'] : 'confirmed')
            ->dateRange('s.sale_date', $f['from'], $f['to'])
            ->whereIf($f['project'], 's.project_id = ?', (int) $f['project'])
            ->whereIf($f['customer'], 's.customer_id = ?', (int) $f['customer'])
            ->whereIf($f['user'], 's.sales_person_id = ?', (int) $f['user'])
            ->search(['s.sale_number', 'c.name', 'c.phone'], $f['q'])
            ->orderBy('s.sale_date DESC, s.id DESC');
        return [
            'columns' => ['sale_number' => ['Sale', 'text'], 'sale_date' => ['Date', 'date'], 'customer' => ['Customer', 'text'], 'project' => ['Project', 'text'],
                'package' => ['Package', 'text'], 'shares' => ['Shares', 'int'], 'subtotal' => ['Subtotal', 'money'], 'discount' => ['Discount', 'money'],
                'fees' => ['Fees', 'money'], 'grand_total' => ['Total', 'money'], 'paid_amount' => ['Paid', 'money'], 'due_amount' => ['Due', 'money'], 'sales_person' => ['Sales person', 'text']],
            'query' => $q,
            'totals' => $q->aggregate(['shares' => 'COALESCE(SUM(s.shares),0)', 'subtotal' => 'COALESCE(SUM(s.subtotal),0)', 'discount' => 'COALESCE(SUM(s.discount),0)',
                'fees' => 'COALESCE(SUM(s.registration_fee + s.development_fee + s.other_fee),0)', 'grand_total' => 'COALESCE(SUM(s.grand_total),0)',
                'paid_amount' => 'COALESCE(SUM(s.paid_amount),0)', 'due_amount' => 'COALESCE(SUM(s.due_amount),0)']),
            'filters' => ['date', 'project', 'customer', 'user', 'sale_status'],
        ];
    }

    private static function collection(array $f, array $cids): array
    {
        $q = Query::table('payments p')
            ->select('p.payment_number, p.payment_date, c.name AS customer, i.invoice_number, pr.project_name AS project, m.name AS method, p.reference, u.name AS received_by, p.amount')
            ->join('JOIN customers c ON c.id = p.customer_id')->join('JOIN invoices i ON i.id = p.invoice_id')
            ->join('JOIN master_data m ON m.id = p.payment_method_id')->join('LEFT JOIN projects pr ON pr.id = p.project_id')
            ->join('LEFT JOIN users u ON u.id = p.received_by')
            ->whereIn('p.company_id', $cids)->where("p.status = 'confirmed'")
            ->dateRange('p.payment_date', $f['from'], $f['to'])
            ->whereIf($f['project'], 'p.project_id = ?', (int) $f['project'])
            ->whereIf($f['customer'], 'p.customer_id = ?', (int) $f['customer'])
            ->whereIf($f['method'], 'p.payment_method_id = ?', (int) $f['method'])
            ->whereIf($f['user'], 'p.received_by = ?', (int) $f['user'])
            ->search(['p.payment_number', 'p.reference', 'c.name', 'i.invoice_number'], $f['q'])
            ->orderBy('p.payment_date DESC, p.id DESC');
        $byMethod = Query::table('payments p')->select('m.name AS label, COUNT(*) AS cnt, SUM(p.amount) AS total')
            ->join('JOIN master_data m ON m.id = p.payment_method_id')
            ->whereIn('p.company_id', $cids)->where("p.status = 'confirmed'")->dateRange('p.payment_date', $f['from'], $f['to'])
            ->whereIf($f['project'], 'p.project_id = ?', (int) $f['project'])
            ->groupBy('m.name')->orderBy('total DESC')->get();
        return [
            'columns' => ['payment_number' => ['Receipt', 'text'], 'payment_date' => ['Date', 'date'], 'customer' => ['Customer', 'text'], 'invoice_number' => ['Invoice', 'text'],
                'project' => ['Project', 'text'], 'method' => ['Method', 'text'], 'reference' => ['Reference', 'text'], 'received_by' => ['Received by', 'text'], 'amount' => ['Amount', 'money']],
            'query' => $q, 'totals' => $q->aggregate(['amount' => 'COALESCE(SUM(p.amount),0)']),
            'summary' => ['By payment method' => $byMethod],
            'filters' => ['date', 'project', 'customer', 'method', 'user'],
        ];
    }

    private static function due(array $f, array $cids): array
    {
        $q = Query::table('invoices i')
            ->select("i.invoice_number, i.invoice_date, i.due_date, c.name AS customer, c.phone, p.project_name AS project, i.grand_total, i.paid_amount, i.due_amount,
                      GREATEST(DATEDIFF(CURDATE(), i.due_date), 0) AS days_overdue, i.status")
            ->join('JOIN customers c ON c.id = i.customer_id')->join('LEFT JOIN projects p ON p.id = i.project_id')
            ->whereIn('i.company_id', $cids)->where('i.deleted_at IS NULL')
            ->where("i.status IN ('unpaid', 'partial', 'overdue')")->where('i.due_amount > 0')
            ->whereIf($f['project'], 'i.project_id = ?', (int) $f['project'])
            ->whereIf($f['customer'], 'i.customer_id = ?', (int) $f['customer'])
            ->dateRange('i.due_date', $f['from'], $f['to'])
            ->search(['i.invoice_number', 'c.name', 'c.phone'], $f['q'])
            ->orderBy('i.due_date ASC');
        $aging = Database::fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN due_date >= CURDATE() OR due_date IS NULL THEN due_amount END), 0) AS `Not yet due`,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 1 AND 30 THEN due_amount END), 0) AS `1–30 days`,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 31 AND 60 THEN due_amount END), 0) AS `31–60 days`,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 61 AND 90 THEN due_amount END), 0) AS `61–90 days`,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) > 90 THEN due_amount END), 0) AS `90+ days`
             FROM invoices WHERE company_id IN (" . Database::placeholders($cids) . ") AND deleted_at IS NULL AND status IN ('unpaid', 'partial', 'overdue') AND due_amount > 0",
            $cids
        ) ?? [];
        return [
            'columns' => ['invoice_number' => ['Invoice', 'text'], 'invoice_date' => ['Date', 'date'], 'due_date' => ['Due date', 'date'], 'customer' => ['Customer', 'text'],
                'phone' => ['Phone', 'text'], 'project' => ['Project', 'text'], 'grand_total' => ['Total', 'money'], 'paid_amount' => ['Paid', 'money'],
                'due_amount' => ['Due', 'money'], 'days_overdue' => ['Days overdue', 'int'], 'status' => ['Status', 'status']],
            'query' => $q,
            'totals' => $q->aggregate(['grand_total' => 'COALESCE(SUM(i.grand_total),0)', 'paid_amount' => 'COALESCE(SUM(i.paid_amount),0)', 'due_amount' => 'COALESCE(SUM(i.due_amount),0)']),
            'summary' => ['Ageing' => array_map(static fn($k, $v) => ['label' => $k, 'cnt' => '', 'total' => $v], array_keys($aging), $aging)],
            'filters' => ['date', 'project', 'customer'], 'date_label' => 'Due date',
        ];
    }

    private static function expense(array $f, array $cids): array
    {
        $q = Query::table('expenses x')
            ->select('x.expense_number, x.expense_date, c.name AS category, p.project_name AS project, m.name AS method, x.reference, x.description, x.amount')
            ->join('JOIN master_data c ON c.id = x.category_id')->join('LEFT JOIN master_data m ON m.id = x.payment_method_id')
            ->join('LEFT JOIN projects p ON p.id = x.project_id')
            ->whereIn('x.company_id', $cids)->where('x.deleted_at IS NULL')->where("x.status = 'approved'")
            ->dateRange('x.expense_date', $f['from'], $f['to'])
            ->whereIf($f['category'], 'x.category_id = ?', (int) $f['category'])
            ->whereIf($f['project'], 'x.project_id = ?', (int) $f['project'])
            ->search(['x.expense_number', 'x.description', 'x.reference'], $f['q'])
            ->orderBy('x.expense_date DESC, x.id DESC');
        $byCat = Query::table('expenses x')->select('c.name AS label, COUNT(*) AS cnt, SUM(x.amount) AS total')
            ->join('JOIN master_data c ON c.id = x.category_id')
            ->whereIn('x.company_id', $cids)->where('x.deleted_at IS NULL')->where("x.status = 'approved'")
            ->dateRange('x.expense_date', $f['from'], $f['to'])->groupBy('c.name')->orderBy('total DESC')->get();
        return [
            'columns' => ['expense_number' => ['Number', 'text'], 'expense_date' => ['Date', 'date'], 'category' => ['Category', 'text'], 'project' => ['Project', 'text'],
                'method' => ['Method', 'text'], 'reference' => ['Reference', 'text'], 'description' => ['Description', 'text'], 'amount' => ['Amount', 'money']],
            'query' => $q, 'totals' => $q->aggregate(['amount' => 'COALESCE(SUM(x.amount),0)']),
            'summary' => ['By category' => $byCat], 'filters' => ['date', 'category', 'project'],
        ];
    }

    private static function leads(array $f, array $cids): array
    {
        $q = Query::table('leads l')
            ->select('l.lead_code, l.created_at, l.name, l.phone, l.email, s.name AS status, src.name AS source, p.project_name AS project, u.name AS assigned_to, l.follow_up_date, IF(l.customer_id IS NULL, \'No\', \'Yes\') AS converted')
            ->join('JOIN master_data s ON s.id = l.status_id')->join('LEFT JOIN master_data src ON src.id = l.source_id')
            ->join('LEFT JOIN projects p ON p.id = l.project_id')->join('LEFT JOIN users u ON u.id = l.assigned_to')
            ->whereIn('l.company_id', $cids)->where('l.deleted_at IS NULL')
            ->dateRange('DATE(l.created_at)', $f['from'], $f['to'])
            ->whereIf($f['project'], 'l.project_id = ?', (int) $f['project'])
            ->whereIf($f['user'], 'l.assigned_to = ?', (int) $f['user'])
            ->whereIf($f['lead_status'], 'l.status_id = ?', (int) $f['lead_status'])
            ->whereIf($f['source'], 'l.source_id = ?', (int) $f['source'])
            ->search(['l.name', 'l.phone', 'l.email', 'l.lead_code'], $f['q'])
            ->orderBy('l.id DESC');
        $own = !AuthService::can('leads.view_all') ? (int) AuthService::id() : null;
        if ($own) {
            $q->where('(l.assigned_to = ? OR l.created_by = ?)', $own, $own);
        }
        $base = static fn() => Query::table('leads l')->whereIn('l.company_id', $cids)->where('l.deleted_at IS NULL')->dateRange('DATE(l.created_at)', $f['from'], $f['to'])
            ->whereIf($f['project'], 'l.project_id = ?', (int) $f['project'])->whereIf($f['user'], 'l.assigned_to = ?', (int) $f['user'])
            ->whereIf($own, '(l.assigned_to = ? OR l.created_by = ?)', $own, $own);
        $byStatus = $base()->select('s.name AS label, COUNT(*) AS cnt, NULL AS total')->join('JOIN master_data s ON s.id = l.status_id')->groupBy('s.name, s.sort_order')->orderBy('s.sort_order')->get();
        $bySource = $base()->select("COALESCE(src.name, '—') AS label, COUNT(*) AS cnt, NULL AS total")->join('LEFT JOIN master_data src ON src.id = l.source_id')->groupBy('src.name')->orderBy('cnt DESC')->get();
        $bySp = $base()->select("COALESCE(u.name, 'Unassigned') AS label, COUNT(*) AS cnt, SUM(l.customer_id IS NOT NULL) AS total")->join('LEFT JOIN users u ON u.id = l.assigned_to')->groupBy('u.name')->orderBy('cnt DESC')->get();
        $all = $base()->aggregate(['n' => 'COUNT(*)', 'conv' => 'COALESCE(SUM(l.customer_id IS NOT NULL),0)']);
        $rate = (int) $all['n'] > 0 ? round((int) $all['conv'] / (int) $all['n'] * 100, 1) : 0;
        return [
            'columns' => ['lead_code' => ['Code', 'text'], 'created_at' => ['Created', 'date'], 'name' => ['Name', 'text'], 'phone' => ['Phone', 'text'], 'email' => ['Email', 'text'],
                'status' => ['Status', 'text'], 'source' => ['Source', 'text'], 'project' => ['Project', 'text'], 'assigned_to' => ['Assigned', 'text'],
                'follow_up_date' => ['Follow-up', 'date'], 'converted' => ['Converted', 'text']],
            'query' => $q, 'totals' => null,
            'summary' => ['By status' => $byStatus, 'By source' => $bySource, 'By sales person (leads / converted)' => $bySp],
            'kpis' => ['Leads' => (int) $all['n'], 'Converted' => (int) $all['conv'], 'Conversion rate' => $rate . '%'],
            'filters' => ['date', 'project', 'user', 'lead_status', 'source'], 'count_summary' => true,
        ];
    }

    private static function customers(array $f, array $cids): array
    {
        $q = Query::table('customers c')
            ->select("c.customer_code, c.name, c.phone, c.email, c.district, ct.name AS type,
                COALESCE((SELECT SUM(ss.shares) FROM share_sales ss WHERE ss.customer_id = c.id AND ss.status = 'confirmed'), 0) AS shares,
                COALESCE((SELECT SUM(i.grand_total) FROM invoices i WHERE i.customer_id = c.id AND i.status NOT IN ('cancelled', 'draft') AND i.deleted_at IS NULL), 0) AS invoiced,
                COALESCE((SELECT SUM(i.paid_amount) FROM invoices i WHERE i.customer_id = c.id AND i.status NOT IN ('cancelled', 'draft') AND i.deleted_at IS NULL), 0) AS paid,
                COALESCE((SELECT SUM(i.due_amount) FROM invoices i WHERE i.customer_id = c.id AND i.status NOT IN ('cancelled', 'draft') AND i.deleted_at IS NULL), 0) AS due,
                c.created_at")
            ->join('LEFT JOIN master_data ct ON ct.id = c.customer_type_id')
            ->whereIn('c.company_id', $cids)->where('c.deleted_at IS NULL')
            ->dateRange('DATE(c.created_at)', $f['from'], $f['to'])
            ->search(['c.name', 'c.phone', 'c.email', 'c.customer_code', 'c.nid'], $f['q'])
            ->orderBy('c.name');
        if (($f['only_due'] ?? '') === '1') {
            $q->where("EXISTS (SELECT 1 FROM invoices i2 WHERE i2.customer_id = c.id AND i2.due_amount > 0 AND i2.status IN ('unpaid', 'partial', 'overdue'))");
        }
        return [
            'columns' => ['customer_code' => ['Code', 'text'], 'name' => ['Customer', 'text'], 'phone' => ['Phone', 'text'], 'email' => ['Email', 'text'], 'district' => ['District', 'text'],
                'type' => ['Type', 'text'], 'shares' => ['Shares', 'int'], 'invoiced' => ['Invoiced', 'money'], 'paid' => ['Paid', 'money'], 'due' => ['Due', 'money'], 'created_at' => ['Since', 'date']],
            'query' => $q, 'totals' => null, 'filters' => ['date', 'only_due'], 'date_label' => 'Created',
        ];
    }

    private static function shares(array $f, array $cids): array
    {
        $q = Query::table('projects p')
            ->select("p.project_code, p.project_name, p.share_unit, p.share_price, p.total_shares,
                COALESCE(SUM(CASE WHEN ss.status = 'confirmed' THEN ss.shares END), 0) AS sold,
                COALESCE(SUM(CASE WHEN ss.status = 'reserved' THEN ss.shares END), 0) AS reserved,
                COALESCE(SUM(CASE WHEN ss.status = 'cancelled' THEN ss.shares END), 0) AS cancelled,
                p.total_shares - COALESCE(SUM(CASE WHEN ss.status IN ('confirmed', 'reserved') THEN ss.shares END), 0) AS available,
                COALESCE(SUM(CASE WHEN ss.status = 'confirmed' THEN ss.grand_total END), 0) AS sales_value")
            ->join('LEFT JOIN share_sales ss ON ss.project_id = p.id')
            ->whereIn('p.company_id', $cids)->where('p.deleted_at IS NULL')
            ->whereIf($f['project'], 'p.id = ?', (int) $f['project'])
            ->search(['p.project_name', 'p.project_code'], $f['q'])
            ->groupBy('p.id')->orderBy('p.project_name');
        return [
            'columns' => ['project_code' => ['Code', 'text'], 'project_name' => ['Project', 'text'], 'share_unit' => ['Share unit', 'text'], 'share_price' => ['Price', 'money'],
                'total_shares' => ['Total', 'int'], 'sold' => ['Sold', 'int'], 'reserved' => ['Reserved', 'int'], 'cancelled' => ['Cancelled', 'int'],
                'available' => ['Available', 'int'], 'sales_value' => ['Sales value', 'money']],
            'query' => $q, 'totals' => null, 'filters' => ['project'],
        ];
    }

    private static function employees(array $f, array $cids): array
    {
        $from = $f['from'] && is_valid_date($f['from']) ? $f['from'] : date('Y-01-01');
        $to = $f['to'] && is_valid_date($f['to']) ? $f['to'] : today();
        $q = Query::table('employees e')
            ->select('e.employee_code, e.name, d.name AS department, g.name AS designation, e.phone, e.joining_date, e.salary,
                COALESCE(sp.paid, 0) AS paid_in_period, e.status')
            ->join('LEFT JOIN master_data d ON d.id = e.department_id')->join('LEFT JOIN master_data g ON g.id = e.designation_id')
            ->join("LEFT JOIN (SELECT employee_id, SUM(net_salary) AS paid FROM salary_payments WHERE status = 'paid' AND payment_date BETWEEN ? AND ? GROUP BY employee_id) sp ON sp.employee_id = e.id", $from, $to)
            ->whereIn('e.company_id', $cids)->where('e.deleted_at IS NULL')
            ->whereIf($f['department'], 'e.department_id = ?', (int) $f['department'])
            ->whereIf($f['emp_status'], 'e.status = ?')
            ->search(['e.name', 'e.employee_code', 'e.phone'], $f['q'])
            ->orderBy('e.name');
        $columns = ['employee_code' => ['Code', 'text'], 'name' => ['Employee', 'text'], 'department' => ['Department', 'text'], 'designation' => ['Designation', 'text'],
            'phone' => ['Phone', 'text'], 'joining_date' => ['Joined', 'date'], 'salary' => ['Basic', 'money'], 'paid_in_period' => ['Salary paid (period)', 'money'], 'status' => ['Status', 'status']];
        if (!AuthService::can('salary.view')) {
            unset($columns['salary'], $columns['paid_in_period']); // salaries hidden without salary.view
        }
        return [
            'columns' => $columns,
            'query' => $q, 'totals' => null, 'filters' => ['date', 'department', 'emp_status'], 'date_label' => 'Salary period',
        ];
    }

    /**
     * Financial summary. Terminology:
     *  - Sales value: confirmed share sales (contracted, not cash)
     *  - Collection: confirmed payments received (cash in)
     *  - Expenses / Salaries: approved expenses + paid salaries (cash out)
     *  - Net cash flow = Collection − Expenses − Salaries  (not "profit")
     */
    public static function financial(array $f): array
    {
        $cids = self::companyScope($f['company'] ?? '');
        $from = $f['from'] && is_valid_date($f['from']) ? $f['from'] : date('Y-01-01');
        $to = $f['to'] && is_valid_date($f['to']) ? $f['to'] : today();
        $in = Database::placeholders($cids);
        $p = [...$cids, $from, $to];
        $sum = static fn(string $sql) => (string) Database::value($sql, $p);
        $sales = $sum("SELECT COALESCE(SUM(grand_total),0) FROM share_sales WHERE company_id IN ($in) AND status = 'confirmed' AND sale_date BETWEEN ? AND ?");
        $invoiced = $sum("SELECT COALESCE(SUM(grand_total),0) FROM invoices WHERE company_id IN ($in) AND status NOT IN ('draft','cancelled') AND deleted_at IS NULL AND invoice_date BETWEEN ? AND ?");
        $collected = $sum("SELECT COALESCE(SUM(amount),0) FROM payments WHERE company_id IN ($in) AND status = 'confirmed' AND payment_date BETWEEN ? AND ?");
        $expenses = $sum("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE company_id IN ($in) AND status = 'approved' AND deleted_at IS NULL AND expense_date BETWEEN ? AND ?");
        $salaries = $sum("SELECT COALESCE(SUM(net_salary),0) FROM salary_payments WHERE company_id IN ($in) AND status = 'paid' AND payment_date BETWEEN ? AND ?");
        $funds = $sum("SELECT COALESCE(SUM(amount),0) FROM fund_contributions WHERE company_id IN ($in) AND status = 'received' AND deleted_at IS NULL AND contribution_date BETWEEN ? AND ?");
        $due = (string) Database::value("SELECT COALESCE(SUM(due_amount),0) FROM invoices WHERE company_id IN ($in) AND status IN ('unpaid','partial','overdue') AND deleted_at IS NULL", $cids);
        $net = Money::toCents($collected) - Money::toCents($expenses) - Money::toCents($salaries);

        $monthly = Database::fetchAll(
            "SELECT m.ym,
                COALESCE((SELECT SUM(grand_total) FROM share_sales WHERE company_id IN ($in) AND status = 'confirmed' AND DATE_FORMAT(sale_date, '%Y-%m') = m.ym), 0) AS sales,
                COALESCE((SELECT SUM(amount) FROM payments WHERE company_id IN ($in) AND status = 'confirmed' AND DATE_FORMAT(payment_date, '%Y-%m') = m.ym), 0) AS collection,
                COALESCE((SELECT SUM(amount) FROM expenses WHERE company_id IN ($in) AND status = 'approved' AND deleted_at IS NULL AND DATE_FORMAT(expense_date, '%Y-%m') = m.ym), 0) AS expenses,
                COALESCE((SELECT SUM(net_salary) FROM salary_payments WHERE company_id IN ($in) AND status = 'paid' AND DATE_FORMAT(payment_date, '%Y-%m') = m.ym), 0) AS salaries
             FROM (" . self::monthsSql($from, $to) . ") m ORDER BY m.ym",
            [...$cids, ...$cids, ...$cids, ...$cids]
        );
        foreach ($monthly as &$row) {
            $row['net'] = Money::fromCents(Money::toCents((string) $row['collection']) - Money::toCents((string) $row['expenses']) - Money::toCents((string) $row['salaries']));
        }
        unset($row);
        return compact('from', 'to', 'sales', 'invoiced', 'collected', 'expenses', 'salaries', 'funds', 'due', 'monthly') + ['net' => Money::fromCents($net)];
    }

    /** Derived table of YYYY-MM months between two dates (max 36), values are code-generated. */
    public static function monthsSql(string $from, string $to): string
    {
        $start = new \DateTime(substr($from, 0, 7) . '-01');
        $end = new \DateTime(substr($to, 0, 7) . '-01');
        $parts = [];
        for ($i = 0; $start <= $end && $i < 36; $i++, $start->modify('+1 month')) {
            $parts[] = "SELECT '" . $start->format('Y-m') . "' AS ym";
        }
        return $parts ? implode(' UNION ALL ', $parts) : "SELECT '" . date('Y-m') . "' AS ym";
    }
}
