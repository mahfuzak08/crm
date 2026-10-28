<?php
declare(strict_types=1);

use App\Controllers as C;

/** @var App\Core\Router $router */

/* ---------- guest ---------- */
$router->group(['middleware' => ['guest']], function ($r) {
    $r->get('/login', [C\AuthController::class, 'showLogin']);
    $r->post('/login', [C\AuthController::class, 'login']);
    $r->get('/forgot-password', [C\AuthController::class, 'showForgot']);
    $r->post('/forgot-password', [C\AuthController::class, 'forgot']);
    $r->get('/reset-password/{token}', [C\AuthController::class, 'showReset']);
    $r->post('/reset-password/{token}', [C\AuthController::class, 'reset']);
});

/* ---------- public (no auth): company logo ---------- */
$router->get('/media/logo/{id}', [C\MediaController::class, 'logo']);

/* ---------- authenticated ---------- */
$router->group(['middleware' => ['auth']], function ($r) {
    $r->post('/logout', [C\AuthController::class, 'logout']);
    $r->get('/', [C\DashboardController::class, 'index']);
    $r->get('/notifications/{id}', [C\DashboardController::class, 'notification']);
    $r->post('/company/switch', [C\CompanyController::class, 'switch'], ['super']);

    $r->get('/profile', [C\ProfileController::class, 'show']);
    $r->post('/profile', [C\ProfileController::class, 'update']);
    $r->get('/profile/password', [C\ProfileController::class, 'showPassword']);
    $r->post('/profile/password', [C\ProfileController::class, 'updatePassword']);

    /* ----- Phase 2: core administration ----- */
    $r->get('/users', [C\UserController::class, 'index']);
    $r->get('/users/create', [C\UserController::class, 'create']);
    $r->post('/users', [C\UserController::class, 'store']);
    $r->get('/users/{id}', [C\UserController::class, 'show']);
    $r->get('/users/{id}/edit', [C\UserController::class, 'edit']);
    $r->post('/users/{id}', [C\UserController::class, 'update']);
    $r->post('/users/{id}/password', [C\UserController::class, 'password']);
    $r->post('/users/{id}/status', [C\UserController::class, 'status']);
    $r->post('/users/{id}/delete', [C\UserController::class, 'destroy']);

    $r->get('/roles', [C\RoleController::class, 'index']);
    $r->get('/roles/create', [C\RoleController::class, 'create']);
    $r->post('/roles', [C\RoleController::class, 'store']);
    $r->get('/roles/{id}/edit', [C\RoleController::class, 'edit']);
    $r->post('/roles/{id}', [C\RoleController::class, 'update']);
    $r->post('/roles/{id}/delete', [C\RoleController::class, 'destroy']);

    $r->get('/companies', [C\CompanyController::class, 'index'], ['super']);
    $r->get('/companies/create', [C\CompanyController::class, 'create'], ['super']);
    $r->post('/companies', [C\CompanyController::class, 'store'], ['super']);
    $r->get('/companies/{id}/edit', [C\CompanyController::class, 'edit'], ['super']);
    $r->post('/companies/{id}', [C\CompanyController::class, 'update'], ['super']);

    $r->get('/settings/company', [C\SettingsController::class, 'company']);
    $r->post('/settings/company', [C\SettingsController::class, 'saveCompany']);
    $r->get('/settings/invoice', [C\SettingsController::class, 'invoice']);
    $r->post('/settings/invoice', [C\SettingsController::class, 'saveInvoice']);
    $r->get('/settings/sales', [C\SettingsController::class, 'sales']);
    $r->post('/settings/sales', [C\SettingsController::class, 'saveSales']);

    $r->get('/master-data', [C\MasterDataController::class, 'index']);
    $r->get('/master-data/{slug}', [C\MasterDataController::class, 'type']);
    $r->post('/master-data/{slug}', [C\MasterDataController::class, 'store']);
    $r->post('/master-data/{slug}/{id}', [C\MasterDataController::class, 'update']);
    $r->post('/master-data/{slug}/{id}/delete', [C\MasterDataController::class, 'destroy']);

    $r->get('/audit-logs', [C\AuditLogController::class, 'index']);

    /* ----- Phase 3: CRM ----- */
    $r->get('/leads', [C\LeadController::class, 'index']);
    $r->get('/leads/create', [C\LeadController::class, 'create']);
    $r->post('/leads', [C\LeadController::class, 'store']);
    $r->get('/leads/{id}', [C\LeadController::class, 'show']);
    $r->get('/leads/{id}/edit', [C\LeadController::class, 'edit']);
    $r->post('/leads/{id}', [C\LeadController::class, 'update']);
    $r->post('/leads/{id}/status', [C\LeadController::class, 'status']);
    $r->post('/leads/{id}/assign', [C\LeadController::class, 'assign']);
    $r->post('/leads/{id}/convert', [C\LeadController::class, 'convert']);
    $r->post('/leads/{id}/share-log', [C\LeadController::class, 'logShare']);
    $r->post('/leads/{id}/delete', [C\LeadController::class, 'destroy']);

    $r->get('/customers', [C\CustomerController::class, 'index']);
    $r->get('/customers/create', [C\CustomerController::class, 'create']);
    $r->post('/customers', [C\CustomerController::class, 'store']);
    $r->get('/customers/{id}', [C\CustomerController::class, 'show']);
    $r->get('/customers/{id}/edit', [C\CustomerController::class, 'edit']);
    $r->post('/customers/{id}', [C\CustomerController::class, 'update']);
    $r->post('/customers/{id}/delete', [C\CustomerController::class, 'destroy']);
    $r->get('/customers/{id}/file/{slug}', [C\CustomerController::class, 'file']);

    $r->get('/directors', [C\DirectorController::class, 'index']);
    $r->get('/directors/create', [C\DirectorController::class, 'create']);
    $r->post('/directors', [C\DirectorController::class, 'store']);
    $r->get('/directors/{id}', [C\DirectorController::class, 'show']);
    $r->get('/directors/{id}/edit', [C\DirectorController::class, 'edit']);
    $r->post('/directors/{id}', [C\DirectorController::class, 'update']);
    $r->post('/directors/{id}/delete', [C\DirectorController::class, 'destroy']);

    $r->get('/activities', [C\ActivityController::class, 'index']);
    $r->post('/activities', [C\ActivityController::class, 'store']);
    $r->post('/activities/{id}/delete', [C\ActivityController::class, 'destroy']);

    $r->get('/documents', [C\DocumentController::class, 'index']);
    $r->post('/documents', [C\DocumentController::class, 'store']);
    $r->get('/document/download/{id}', [C\DocumentController::class, 'download']);
    $r->get('/documents/{id}/download', [C\DocumentController::class, 'download']);
    $r->post('/documents/{id}/share', [C\DocumentController::class, 'share']);
    $r->post('/documents/{id}/delete', [C\DocumentController::class, 'destroy']);

    $r->get('/gallery', [C\GalleryController::class, 'index']);
    $r->post('/gallery/project/{id}', [C\GalleryController::class, 'store']);
    $r->post('/gallery/project/{id}/{id}', [C\GalleryController::class, 'update']);
    $r->post('/gallery/{id}/delete', [C\GalleryController::class, 'destroy']);
    $r->get('/gallery/{id}/file', [C\GalleryController::class, 'file']);

    $r->get('/offers', [C\OfferController::class, 'index']);
    $r->get('/offers/create', [C\OfferController::class, 'create']);
    $r->post('/offers', [C\OfferController::class, 'store']);
    $r->get('/offers/{id}', [C\OfferController::class, 'show']);
    $r->get('/offers/{id}/edit', [C\OfferController::class, 'edit']);
    $r->post('/offers/{id}', [C\OfferController::class, 'update']);
    $r->post('/offers/{id}/send', [C\OfferController::class, 'send']);
    $r->post('/offers/{id}/status', [C\OfferController::class, 'status']);
    $r->post('/offers/{id}/convert', [C\OfferController::class, 'convert']);
    $r->post('/offers/{id}/delete', [C\OfferController::class, 'destroy']);
    $r->get('/offers/{id}/print', [C\OfferController::class, 'print']);

    /* ----- Phase 4: projects & sales ----- */
    $r->get('/projects', [C\ProjectController::class, 'index']);
    $r->get('/projects/create', [C\ProjectController::class, 'create']);
    $r->post('/projects', [C\ProjectController::class, 'store']);
    $r->get('/projects/{id}', [C\ProjectController::class, 'show']);
    $r->get('/projects/{id}/edit', [C\ProjectController::class, 'edit']);
    $r->post('/projects/{id}', [C\ProjectController::class, 'update']);
    $r->post('/projects/{id}/delete', [C\ProjectController::class, 'destroy']);
    $r->post('/projects/{id}/packages', [C\ProjectController::class, 'storePackage']);
    $r->post('/projects/{id}/packages/{id}', [C\ProjectController::class, 'updatePackage']);
    $r->post('/projects/{id}/packages/{id}/delete', [C\ProjectController::class, 'destroyPackage']);
    $r->get('/shares', [C\ProjectController::class, 'shares']);

    $r->get('/sales', [C\SaleController::class, 'index']);
    $r->get('/sales/create', [C\SaleController::class, 'create']);
    $r->post('/sales', [C\SaleController::class, 'store']);
    $r->get('/sales/{id}', [C\SaleController::class, 'show']);
    $r->post('/sales/{id}/confirm', [C\SaleController::class, 'confirm']);
    $r->post('/sales/{id}/cancel', [C\SaleController::class, 'cancel']);
    $r->get('/sales/{id}/agreement', [C\SaleController::class, 'agreement']);

    $r->get('/invoices', [C\InvoiceController::class, 'index']);
    $r->get('/invoices/create', [C\InvoiceController::class, 'create']);
    $r->post('/invoices', [C\InvoiceController::class, 'store']);
    $r->get('/invoices/{id}', [C\InvoiceController::class, 'show']);
    $r->get('/invoices/{id}/edit', [C\InvoiceController::class, 'edit']);
    $r->post('/invoices/{id}', [C\InvoiceController::class, 'update']);
    $r->post('/invoices/{id}/issue', [C\InvoiceController::class, 'issue']);
    $r->post('/invoices/{id}/cancel', [C\InvoiceController::class, 'cancel']);
    $r->get('/invoices/{id}/print', [C\InvoiceController::class, 'print']);

    $r->get('/payments', [C\PaymentController::class, 'index']);
    $r->get('/payments/create', [C\PaymentController::class, 'create']);
    $r->post('/payments', [C\PaymentController::class, 'store']);
    $r->get('/payments/{id}', [C\PaymentController::class, 'show']);
    $r->post('/payments/{id}', [C\PaymentController::class, 'update']);
    $r->post('/payments/{id}/cancel', [C\PaymentController::class, 'cancel']);
    $r->get('/payments/{id}/receipt', [C\PaymentController::class, 'receipt']);

    $r->get('/statements', [C\StatementController::class, 'index']);

    /* ----- Phase 5: HR & finance ----- */
    $r->get('/employees', [C\EmployeeController::class, 'index']);
    $r->get('/employees/create', [C\EmployeeController::class, 'create']);
    $r->post('/employees', [C\EmployeeController::class, 'store']);
    $r->get('/employees/{id}', [C\EmployeeController::class, 'show']);
    $r->get('/employees/{id}/edit', [C\EmployeeController::class, 'edit']);
    $r->post('/employees/{id}', [C\EmployeeController::class, 'update']);
    $r->post('/employees/{id}/delete', [C\EmployeeController::class, 'destroy']);
    $r->get('/employees/{id}/photo', [C\EmployeeController::class, 'photo']);

    $r->get('/salary', [C\SalaryController::class, 'index']);
    $r->get('/salary/create', [C\SalaryController::class, 'create']);
    $r->post('/salary', [C\SalaryController::class, 'store']);
    $r->get('/salary/components', [C\SalaryController::class, 'components']);
    $r->post('/salary/components', [C\SalaryController::class, 'storeComponent']);
    $r->post('/salary/components/{id}', [C\SalaryController::class, 'updateComponent']);
    $r->post('/salary/components/{id}/delete', [C\SalaryController::class, 'destroyComponent']);
    $r->get('/salary/{id}', [C\SalaryController::class, 'show']);
    $r->get('/salary/{id}/payslip', [C\SalaryController::class, 'payslip']);
    $r->post('/salary/{id}/cancel', [C\SalaryController::class, 'cancel']);

    $r->get('/expenses', [C\ExpenseController::class, 'index']);
    $r->get('/expenses/create', [C\ExpenseController::class, 'create']);
    $r->post('/expenses', [C\ExpenseController::class, 'store']);
    $r->get('/expenses/{id}', [C\ExpenseController::class, 'show']);
    $r->get('/expenses/{id}/edit', [C\ExpenseController::class, 'edit']);
    $r->post('/expenses/{id}', [C\ExpenseController::class, 'update']);
    $r->post('/expenses/{id}/approve', [C\ExpenseController::class, 'approve']);
    $r->post('/expenses/{id}/cancel', [C\ExpenseController::class, 'cancel']);
    $r->get('/expenses/{id}/attachment', [C\ExpenseController::class, 'attachment']);
    $r->get('/expenses/{id}/voucher', [C\ExpenseController::class, 'voucher']);

    $r->get('/funds', [C\FundController::class, 'index']);
    $r->get('/funds/create', [C\FundController::class, 'create']);
    $r->post('/funds', [C\FundController::class, 'store']);
    $r->get('/funds/{id}', [C\FundController::class, 'show']);
    $r->get('/funds/{id}/edit', [C\FundController::class, 'edit']);
    $r->post('/funds/{id}', [C\FundController::class, 'update']);
    $r->post('/funds/{id}/cancel', [C\FundController::class, 'cancel']);
    $r->get('/funds/{id}/attachment', [C\FundController::class, 'attachment']);
    $r->get('/funds/{id}/receipt', [C\FundController::class, 'receipt']);

    /* ----- Phase 6: reports ----- */
    $r->get('/reports', [C\ReportController::class, 'index']);
    $r->get('/reports/{slug}', [C\ReportController::class, 'show']);

    /* ----- System ----- */
    $r->get('/backup', [C\BackupController::class, 'index'], ['super']);
    $r->post('/backup', [C\BackupController::class, 'store'], ['super']);
    $r->get('/backup/download', [C\BackupController::class, 'download'], ['super']);
    $r->post('/backup/delete', [C\BackupController::class, 'destroy'], ['super']);

    /* ----- AJAX ----- */
    $r->get('/api/projects/{id}/shares', [C\ProjectController::class, 'apiShares']);
    $r->get('/api/lookup/customers', [C\ApiController::class, 'customers']);
    $r->get('/api/lookup/leads', [C\ApiController::class, 'leads']);
    $r->get('/api/invoices/open', [C\ApiController::class, 'openInvoices']);
    $r->get('/search', [C\ApiController::class, 'search']);
});

/* ---------- public signed share links (no login) ---------- */
$router->get('/share/{slug}/{id}/{id}/{token}', [C\ShareController::class, 'show']);
