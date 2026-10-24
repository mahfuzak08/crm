<?php
declare(strict_types=1);

/**
 * Permission catalog (security behaviour lives in code). Synced into the
 * `permissions` table by the installer / `php cli.php sync-permissions`.
 * Roles and their permission assignments are database-driven.
 */
return [
    'modules' => [
        'dashboard'   => ['dashboard.view' => 'View dashboard'],
        'companies'   => ['companies.view' => 'View companies', 'companies.manage' => 'Create/edit companies (multi-company)'],
        'users'       => ['users.view' => 'View users', 'users.create' => 'Create users', 'users.edit' => 'Edit users / reset password', 'users.delete' => 'Deactivate/delete users'],
        'roles'       => ['roles.view' => 'View roles', 'roles.create' => 'Create roles', 'roles.edit' => 'Edit roles & permissions', 'roles.delete' => 'Delete roles'],
        'leads'       => ['leads.view' => 'View leads (own/assigned)', 'leads.view_all' => 'View all leads', 'leads.create' => 'Create leads', 'leads.edit' => 'Edit leads', 'leads.assign' => 'Assign leads', 'leads.delete' => 'Delete leads', 'leads.convert' => 'Convert lead to customer'],
        'activities'  => ['activities.view' => 'View activities', 'activities.create' => 'Log activities', 'activities.delete' => 'Delete activities'],
        'customers'   => ['customers.view' => 'View customers', 'customers.create' => 'Create customers', 'customers.edit' => 'Edit customers', 'customers.delete' => 'Delete customers', 'customers.statement' => 'View customer statement'],
        'documents'   => ['documents.view' => 'View/download documents', 'documents.upload' => 'Upload documents', 'documents.delete' => 'Delete documents', 'documents.share' => 'Share documents by link'],
        'gallery'     => ['gallery.view' => 'View gallery', 'gallery.manage' => 'Manage gallery'],
        'projects'    => ['projects.view' => 'View projects', 'projects.create' => 'Create projects', 'projects.edit' => 'Edit projects', 'projects.delete' => 'Delete projects'],
        'shares'      => ['shares.view' => 'View share inventory & packages', 'shares.create' => 'Create share packages', 'shares.edit' => 'Edit share packages', 'shares.delete' => 'Delete share packages'],
        'sales'       => ['sales.view' => 'View share sales', 'sales.create' => 'Create share sales', 'sales.cancel' => 'Cancel share sales', 'sales.discount' => 'Give discount above company limit'],
        'offers'      => ['offers.view' => 'View offers', 'offers.create' => 'Create offers', 'offers.edit' => 'Edit offers / change status', 'offers.send' => 'Send offers', 'offers.delete' => 'Delete offers'],
        'invoices'    => ['invoices.view' => 'View invoices', 'invoices.create' => 'Create invoices', 'invoices.edit' => 'Edit draft invoices', 'invoices.delete' => 'Cancel invoices', 'invoices.print' => 'Print invoices'],
        'payments'    => ['payments.view' => 'View payments & receipts', 'payments.create' => 'Receive payments', 'payments.edit' => 'Edit payment notes/reference', 'payments.delete' => 'Cancel (reverse) payments'],
        'employees'   => ['employees.view' => 'View employees', 'employees.create' => 'Create employees', 'employees.edit' => 'Edit employees', 'employees.delete' => 'Delete employees'],
        'salary'      => ['salary.view' => 'View salary', 'salary.manage' => 'Manage salary structure & components', 'salary.pay' => 'Pay / cancel salary'],
        'expenses'    => ['expenses.view' => 'View expenses', 'expenses.create' => 'Create expenses', 'expenses.edit' => 'Edit expenses', 'expenses.delete' => 'Cancel expenses', 'expenses.approve' => 'Approve expenses'],
        'funds'       => ['funds.view' => 'View fund contributions', 'funds.create' => 'Record fund contributions', 'funds.edit' => 'Edit fund contributions', 'funds.delete' => 'Cancel fund contributions'],
        'reports'     => ['reports.view' => 'View reports', 'reports.export' => 'Export reports (CSV)'],
        'settings'    => ['settings.view' => 'View settings', 'settings.edit' => 'Edit settings', 'master_data.manage' => 'Manage master data'],
        'system'      => ['audit_logs.view' => 'View audit logs', 'backup.manage' => 'Create/download database backups'],
    ],

    /* Default role => permission patterns ('*' = all, 'module.*' = all in module). */
    'default_roles' => [
        'super_admin'     => ['name' => 'Super Admin', 'perms' => ['*'], 'description' => 'Full system access across all companies'],
        'admin'           => ['name' => 'Admin', 'perms' => ['*', '!companies.manage', '!backup.manage'], 'description' => 'Full access within the company'],
        'manager'         => ['name' => 'Manager', 'perms' => ['dashboard.*', 'leads.*', 'activities.*', 'customers.*', 'documents.*', 'gallery.*', 'projects.view', 'projects.create', 'projects.edit', 'shares.*', 'sales.*', 'offers.*', 'invoices.view', 'invoices.create', 'invoices.print', 'payments.view', 'payments.create', 'employees.view', 'expenses.view', 'expenses.create', 'reports.*', 'users.view']],
        'director'        => ['name' => 'Director', 'perms' => ['*.view', 'leads.view_all', 'customers.statement', 'invoices.print', 'reports.*', 'expenses.approve']],
        'share_holder'    => ['name' => 'Share Holder', 'perms' => ['dashboard.view', 'projects.view', 'shares.view', 'gallery.view']],
        'sales_executive' => ['name' => 'Sales Executive', 'perms' => ['dashboard.view', 'leads.view', 'leads.create', 'leads.edit', 'leads.convert', 'activities.view', 'activities.create', 'customers.view', 'customers.create', 'customers.edit', 'documents.view', 'documents.upload', 'documents.share', 'gallery.view', 'projects.view', 'shares.view', 'offers.view', 'offers.create', 'offers.edit', 'offers.send', 'sales.view', 'sales.create', 'invoices.view', 'invoices.print']],
        'accountant'      => ['name' => 'Accountant', 'perms' => ['dashboard.view', 'customers.view', 'customers.statement', 'projects.view', 'shares.view', 'sales.view', 'invoices.*', 'payments.*', 'expenses.*', 'funds.*', 'salary.*', 'employees.view', 'reports.*']],
        'employee'        => ['name' => 'Employee', 'perms' => ['dashboard.view', 'leads.view', 'activities.view', 'activities.create', 'projects.view', 'gallery.view']],
        'customer'        => ['name' => 'Customer', 'perms' => [], 'description' => 'Reserved for the future customer portal'],
    ],
];
