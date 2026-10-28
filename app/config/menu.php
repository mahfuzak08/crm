<?php
declare(strict_types=1);

/**
 * Sidebar menu. Each item is shown only if the user holds `perm`.
 * Groups are hidden when no child is visible.
 */
return [
    ['label' => 'Dashboard', 'icon' => 'speedometer2', 'url' => '/', 'perm' => 'dashboard.view'],

    ['label' => 'CRM', 'icon' => 'people', 'children' => [
        ['label' => 'Leads', 'url' => '/leads', 'perm' => 'leads.view'],
        ['label' => 'Customers', 'url' => '/customers', 'perm' => 'customers.view'],
        ['label' => 'Board of Directors', 'url' => '/directors', 'perm' => 'directors.view'],
        ['label' => 'Activities', 'url' => '/activities', 'perm' => 'activities.view'],
        ['label' => 'Offers', 'url' => '/offers', 'perm' => 'offers.view'],
        ['label' => 'Documents', 'url' => '/documents', 'perm' => 'documents.view'],
    ]],

    ['label' => 'Projects', 'icon' => 'buildings', 'children' => [
        ['label' => 'Projects', 'url' => '/projects', 'perm' => 'projects.view'],
        ['label' => 'Shares', 'url' => '/shares', 'perm' => 'shares.view'],
        ['label' => 'Gallery', 'url' => '/gallery', 'perm' => 'gallery.view'],
    ]],

    ['label' => 'Sales', 'icon' => 'cart-check', 'children' => [
        ['label' => 'Share Sales', 'url' => '/sales', 'perm' => 'sales.view'],
        ['label' => 'Invoices', 'url' => '/invoices', 'perm' => 'invoices.view'],
        ['label' => 'Payments', 'url' => '/payments', 'perm' => 'payments.view'],
        ['label' => 'Receipts', 'url' => '/payments?view=receipts', 'perm' => 'payments.view'],
        ['label' => 'Customer Statement', 'url' => '/statements', 'perm' => 'customers.statement'],
    ]],

    ['label' => 'HR', 'icon' => 'person-badge', 'children' => [
        ['label' => 'Employees', 'url' => '/employees', 'perm' => 'employees.view'],
        ['label' => 'Departments', 'url' => '/master-data/department', 'perm' => 'master_data.manage'],
        ['label' => 'Designations', 'url' => '/master-data/designation', 'perm' => 'master_data.manage'],
        ['label' => 'Salary', 'url' => '/salary', 'perm' => 'salary.view'],
    ]],

    ['label' => 'Finance', 'icon' => 'wallet2', 'children' => [
        ['label' => 'Expenses', 'url' => '/expenses', 'perm' => 'expenses.view'],
        ['label' => 'Fund Contributions', 'url' => '/funds', 'perm' => 'funds.view'],
        ['label' => 'Profit / Loss', 'url' => '/reports/financial', 'perm' => 'reports.view'],
    ]],

    ['label' => 'Reports', 'icon' => 'bar-chart-line', 'url' => '/reports', 'perm' => 'reports.view'],

    ['label' => 'Settings', 'icon' => 'gear', 'children' => [
        ['label' => 'Company', 'url' => '/settings/company', 'perm' => 'settings.view'],
        ['label' => 'Invoice & Numbering', 'url' => '/settings/invoice', 'perm' => 'settings.view'],
        ['label' => 'Sales & Offers', 'url' => '/settings/sales', 'perm' => 'settings.view'],
        ['label' => 'Payment Methods', 'url' => '/master-data/payment_method', 'perm' => 'master_data.manage'],
        ['label' => 'Salary Components', 'url' => '/salary/components', 'perm' => 'salary.manage'],
        ['label' => 'Expense Categories', 'url' => '/master-data/expense_category', 'perm' => 'master_data.manage'],
        ['label' => 'Master Data', 'url' => '/master-data', 'perm' => 'master_data.manage'],
        ['label' => 'Users', 'url' => '/users', 'perm' => 'users.view'],
        ['label' => 'Roles & Permissions', 'url' => '/roles', 'perm' => 'roles.view'],
        ['label' => 'Companies', 'url' => '/companies', 'perm' => 'companies.manage', 'super' => true],
    ]],

    ['label' => 'System', 'icon' => 'shield-lock', 'children' => [
        ['label' => 'Audit Logs', 'url' => '/audit-logs', 'perm' => 'audit_logs.view'],
        ['label' => 'Backup', 'url' => '/backup', 'perm' => 'backup.manage', 'super' => true],
    ]],
];
