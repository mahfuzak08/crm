# Senior Software Developer Task: Build "Lake View Iconic Tower CRM"

You are a **Senior PHP Software Architect, Security Engineer, Database Designer, and Full-Stack Developer**.

Build a production-ready, lightweight CRM named:

**Lake View Iconic Tower CRM**

The system will be used for a **Share-Based Land/Real Estate Project**, where land/project shares can be offered and sold to customers/shareholders.

The application must be developed using:

* PHP 8.2+
* MySQL 8+
* HTML5
* CSS3
* Vanilla JavaScript
* AJAX / Fetch API where appropriate
* Bootstrap 5 or a very lightweight CSS approach
* PDO with prepared statements
* No Laravel
* No CodeIgniter
* No heavy PHP framework
* No unnecessary dependencies

The goal is:

> **Simple + Lightweight + Secure + Modular + Maintainable + Multi-Company Ready**

---

# 1. IMPORTANT DEVELOPMENT PRINCIPLES

Do NOT build this as a quick prototype.

Build it as a small professional CRM application that can later grow into a SaaS/multi-company system.

Follow these principles:

1. Security first.
2. Database-driven configuration.
3. Reusable components.
4. Strict separation of concerns.
5. Avoid duplicated code.
6. Avoid hard-coded business rules.
7. Use reusable helper functions/classes.
8. Use AJAX only where it improves UX.
9. Use server-side validation for everything.
10. Never trust client-side validation.
11. Use PDO prepared statements everywhere.
12. Never directly concatenate user input into SQL.
13. Use CSRF protection on all state-changing requests.
14. Use secure password hashing.
15. Use session security best practices.
16. Use RBAC permissions.
17. Implement audit logging.
18. Secure uploaded files.
19. Never expose sensitive files directly.
20. Never expose database credentials.
21. Use environment/configuration separation.
22. Make the system easy to deploy on normal shared hosting/cPanel.
23. Do not over-engineer.

---

# 2. APPLICATION ARCHITECTURE

Use a lightweight MVC-inspired architecture.

Suggested structure:

```text
lake-view-crm/
│
├── app/
│   ├── config/
│   │   ├── app.php
│   │   ├── database.php
│   │   └── constants.php
│   │
│   ├── controllers/
│   ├── models/
│   ├── services/
│   ├── helpers/
│   ├── middleware/
│   └── views/
│
├── bootstrap/
│   └── app.php
│
├── public/
│   ├── index.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── .htaccess
│
├── routes/
│   └── web.php
│
├── storage/
│   ├── logs/
│   ├── uploads/
│   └── private/
│
├── database/
│   ├── migrations/
│   └── seeders/
│
├── views/
│
├── .env.example
├── .gitignore
└── README.md
```

If you believe a simpler structure is more appropriate for raw PHP, you may modify the structure, but maintain clear separation between:

* Controllers
* Models
* Views
* Services
* Helpers
* Middleware
* Configuration

Do not create hundreds of unnecessary files.

---

# 3. MULTI-COMPANY READY DESIGN

Although the first company/project is:

**Lake View Iconic Tower**

the architecture must support multiple companies/projects in the future.

Example:

```text
Company A
Company B
Company C
```

The database should use a `companies` table.

Most business-related tables should contain:

```text
company_id
```

Examples:

* users
* customers
* leads
* invoices
* payments
* employees
* expenses
* projects
* shares
* documents
* offers

Do NOT hard-code:

```text
Lake View Iconic Tower
```

inside PHP business logic.

It should come from the database/company setup.

---

# 4. FIRST USER / SUPER ADMIN

During installation/setup, create the first user as:

```text
Super Admin
```

The Super Admin has full system access.

The system should support:

```text
Super Admin
Admin
Manager
Director
Share Holder
Sales Executive
Accountant
Employee
Customer
Other Custom Roles
```

Do NOT hard-code all roles permanently.

Roles must be database-driven.

---

# 5. RBAC — ROLE BASED ACCESS CONTROL

Implement proper permission management.

Tables should include something similar to:

```text
roles
permissions
role_permissions
user_roles
```

Permissions example:

```text
dashboard.view

users.view
users.create
users.edit
users.delete

roles.view
roles.create
roles.edit
roles.delete

leads.view
leads.create
leads.edit
leads.delete

customers.view
customers.create
customers.edit
customers.delete

projects.view
projects.create
projects.edit
projects.delete

shares.view
shares.create
shares.edit
shares.delete

offers.view
offers.create
offers.send

invoices.view
invoices.create
invoices.edit
invoices.delete
invoices.print

payments.view
payments.create
payments.edit
payments.delete

employees.view
employees.create
employees.edit

expenses.view
expenses.create
expenses.edit
expenses.delete

reports.view

settings.view
settings.edit

audit_logs.view
```

Every protected controller/action must check permissions server-side.

Never rely only on hiding menu items.

---

# 6. USER MANAGEMENT

Create a complete user management module.

Features:

* Create user
* Edit user
* View user
* Activate/deactivate
* Reset password
* Assign roles
* Assign company
* Assign multiple roles if required
* Login history
* Last login
* Account status

User fields:

```text
id
company_id
name
email
phone
username
password
status
avatar
last_login_at
created_at
updated_at
```

Password:

Use:

```php
password_hash()
password_verify()
```

Never store plain-text passwords.

---

# 7. PROJECT MANAGEMENT

Because this is a share-based land project, create a project module.

Example:

```text
Lake View Iconic Tower
```

Project fields should be configurable.

Suggested fields:

```text
project_id
company_id
project_name
project_code
location
description
total_land_area
total_shares
share_unit
share_price
registration_fee
development_fee
status
start_date
expected_completion_date
created_at
updated_at
```

Do not assume only one project.

A company should be able to have multiple projects.

---

# 8. LAND / SHARE MANAGEMENT

Create a proper Share Management module.

Features:

* Share types
* Share packages
* Share price
* Total shares
* Available shares
* Reserved shares
* Sold shares
* Transferred shares
* Cancelled shares

Possible tables:

```text
share_types
share_packages
project_shares
share_transactions
```

Example:

```text
1 Share = X amount of land
```

The system should calculate:

```text
Total Shares
Sold Shares
Available Shares
Reserved Shares
```

Avoid hard-coded values.

---

# 9. LEAD MANAGEMENT

Create a complete Lead CRM.

Lead fields:

```text
id
company_id
project_id
name
phone
email
address
source
status
assigned_to
notes
created_at
updated_at
```

Lead status should be dynamic.

Examples:

```text
New
Contacted
Interested
Offer Sent
Follow Up
Negotiation
Converted
Lost
```

Create a dynamic Lead Status setup.

Lead features:

* Add lead
* Edit lead
* Assign sales person
* Add notes
* Follow-up date
* Call history
* Activity history
* Send offer
* Send WhatsApp/message link
* Email
* Share project gallery
* Share documents
* Share videos
* Convert lead to customer

---

# 10. PROJECT GALLERY

Create a gallery system.

A project can have:

* Images
* Videos
* Documents

Gallery items should support:

```text
title
description
file
type
project_id
status
sort_order
```

Types:

```text
image
video
document
```

Allowed image formats:

```text
jpg
jpeg
png
webp
```

Documents:

```text
pdf
doc
docx
xls
xlsx
```

Videos:

Prefer storing secure video URLs or controlled uploads rather than blindly allowing arbitrary executable/media files.

---

# 11. DOCUMENT MANAGEMENT

Create secure document management.

Examples:

* Land documents
* Deed / Dolil
* Project brochure
* Agreement
* Payment receipt
* Customer NID
* Other documents

IMPORTANT:

Uploaded private documents must NOT be directly accessible using a public URL.

Use a secure download controller:

```text
/document/download/{id}
```

The controller must:

1. Authenticate user.
2. Check permission.
3. Check company ownership.
4. Check document ownership/access.
5. Then stream the file.

Store files using randomized filenames.

Never trust the original filename.

---

# 12. CUSTOMER MANAGEMENT

Create Customer module.

Customer fields:

```text
id
company_id
customer_code
name
father_name
mother_name
spouse_name
phone
alternate_phone
email
nid
passport
date_of_birth
occupation
address
city
district
country
photo
nid_front
nid_back
notes
status
created_at
updated_at
```

Do not make every field mandatory.

Company/project-specific fields should eventually be configurable.

---

# 13. CUSTOMER SHARE PURCHASE

Customer should be able to purchase shares.

Create a transaction such as:

```text
Share Sale
```

Information:

```text
customer
project
share/package
quantity
unit_price
discount
registration_fee
development_fee
other_fee
total_amount
paid_amount
due_amount
sale_date
status
```

Automatically calculate:

```text
subtotal
discount
fees
grand_total
paid
due
```

All financial calculations must happen server-side.

Never trust totals sent by JavaScript.

---

# 14. OFFER MANAGEMENT

Create an Offer module.

A lead/customer can receive an offer.

Offer should contain:

```text
offer_number
company
project
customer/lead
share/package
quantity
unit_price
discount
fees
total
valid_until
notes
status
```

Statuses:

```text
Draft
Sent
Viewed
Accepted
Rejected
Expired
Converted
```

Generate printable/PDF-ready offer layout.

Also provide:

```text
Send Offer
```

via email when email functionality is configured.

---

# 15. INVOICE MODULE

Create a professional invoice system.

Invoice fields:

```text
invoice_id
company_id
customer_id
project_id
invoice_number
invoice_date
due_date
subtotal
discount
tax
other_charge
grand_total
paid_amount
due_amount
status
notes
```

Statuses:

```text
Draft
Unpaid
Partial
Paid
Cancelled
Overdue
```

Invoice numbering must be configurable.

Example:

```text
INV-2026-000001
```

Invoice settings should allow:

```text
prefix
starting_number
yearly_reset
number_length
```

Invoice template should be configurable.

---

# 16. PAYMENT MODULE

Create payment management.

Payment fields:

```text
payment_id
company_id
customer_id
invoice_id
project_id
payment_number
payment_date
amount
payment_method
reference
notes
received_by
status
```

Payment methods should be dynamic.

Examples:

```text
Cash
bKash
Nagad
Bank
Cheque
Rocket
Other
```

Do not hard-code payment methods.

Create:

```text
payment_methods
```

table.

When payment is created:

```text
invoice paid_amount += payment
invoice due_amount = grand_total - paid_amount
```

Do all financial updates inside a database transaction.

---

# 17. RECEIPT

Every confirmed payment should have a printable receipt.

Example:

```text
Payment Receipt

Company
Customer
Invoice
Payment Number
Payment Date
Amount
Payment Method
Reference
Received By
```

Provide:

```text
Print
Download/Save as PDF
```

---

# 18. EMPLOYEE MANAGEMENT

Create Employee module.

Fields:

```text
employee_code
name
phone
email
address
designation
department
joining_date
salary
status
photo
```

Create:

```text
departments
designations
```

as dynamic setup modules.

---

# 19. SALARY SETUP

Create salary configuration.

Support:

```text
Basic Salary
Allowances
Deductions
Bonus
Commission
Net Salary
```

Create:

```text
salary_components
employee_salary
salary_payments
```

Salary components must be dynamic.

Example:

```text
House Rent
Transport
Medical
Mobile Bill
Commission
Tax
Other Deduction
```

---

# 20. EXPENSE MODULE

Create expense management.

Fields:

```text
expense_number
company_id
expense_category
date
amount
payment_method
reference
description
attachment
created_by
status
```

Expense categories must be dynamic.

Examples:

```text
Office Rent
Utilities
Marketing
Transport
Salary
Maintenance
Other
```

---

# 21. DASHBOARD

Create a clean modern dashboard.

Dashboard cards:

```text
Total Leads
New Leads
Total Customers
Total Projects
Available Shares
Sold Shares
Total Sales
Total Invoice
Total Collection
Total Due
Total Expense
Total Employee
```

Charts:

```text
Monthly Sales
Monthly Collection
Monthly Expense
Lead Conversion
```

Dashboard data must be company-aware.

Super Admin may see all companies.

Normal users only see authorized company data.

---

# 22. REPORTS

Create reporting system.

Minimum reports:

### Sales Report

Filters:

```text
Date From
Date To
Company
Project
Customer
Sales Person
```

### Collection Report

### Due Report

### Expense Report

### Lead Report

### Customer Report

### Share Inventory Report

### Employee Report

### Profit/Loss Summary

Reports should support:

```text
Search
Filter
Pagination
Print
CSV Export
```

---

# 23. SETUP MODULE

Create a dynamic Setup section.

## Company Setup

Fields:

```text
Company Name
Logo
Address
Phone
Email
Website
Tax/VAT information
Invoice footer
Receipt footer
Currency
Timezone
Date format
```

---

## Invoice Setup

Allow:

```text
Invoice prefix
Starting number
Number length
Reset yearly
Default due days
Invoice footer
Terms & conditions
```

---

## Payment Setup

Dynamic payment methods.

Example:

```text
Cash
Bank
bKash
Nagad
Rocket
Cheque
```

Admin can:

```text
Add
Edit
Disable
Delete
```

---

## Salary Setup

Dynamic:

```text
Salary components
Allowances
Deductions
Commission types
```

---

## Expense Setup

Dynamic:

```text
Expense categories
Payment methods
Expense statuses
```

---

# 24. DYNAMIC MASTER DATA

Make frequently changing data configurable.

Create a generic master data approach where appropriate.

Examples:

```text
Lead Source
Lead Status
Customer Type
Project Status
Share Type
Payment Method
Expense Category
Employee Department
Employee Designation
Document Type
Activity Type
```

Avoid creating unnecessary hard-coded enums.

However, do NOT make everything dynamically configurable.

Security-critical values and system-level behavior should remain controlled by code.

---

# 25. ACTIVITY / CRM TIMELINE

Create an activity system.

Activities:

```text
Call
Meeting
SMS
Email
WhatsApp
Follow-up
Note
Offer Sent
Payment
Document Shared
```

Customer/lead timeline should show:

```text
Date
User
Activity
Description
Attachment
```

---

# 26. AUDIT LOG

This is mandatory.

Create:

```text
audit_logs
```

Track:

```text
user_id
company_id
action
module
record_id
old_values
new_values
IP address
user agent
created_at
```

Examples:

```text
LOGIN
LOGOUT
CREATE_CUSTOMER
UPDATE_CUSTOMER
DELETE_CUSTOMER
CREATE_INVOICE
UPDATE_INVOICE
CREATE_PAYMENT
DELETE_PAYMENT
CHANGE_SETTINGS
```

Sensitive data should not be logged unnecessarily.

---

# 27. SECURITY REQUIREMENTS

Security is extremely important.

Implement at minimum:

### SQL Injection

Use PDO prepared statements everywhere.

Never:

```php
$sql = "SELECT * FROM users WHERE id = ".$id;
```

Use prepared statements.

---

### Password Security

Use:

```php
password_hash()
password_verify()
```

Prefer:

```php
PASSWORD_DEFAULT
```

---

### CSRF

Every POST/PUT/DELETE/state-changing request must use CSRF protection.

Create reusable:

```php
csrf_token()
csrf_field()
verify_csrf()
```

---

### XSS

Escape output using a helper:

```php
e($value)
```

Use proper HTML escaping.

---

### Session Security

Configure:

```text
HttpOnly
Secure when HTTPS
SameSite=Lax or Strict
Session regeneration after login
Session timeout
Logout session destruction
```

Prevent session fixation.

---

### Authentication

Implement:

```text
Login
Logout
Forgot Password
Reset Password
Change Password
```

Login should support:

```text
Email OR username OR phone
```

if configured.

Add login throttling/rate limiting.

---

### Authorization

Every sensitive action must check:

```text
Authentication
Permission
Company ownership
Record ownership/access
```

Do not trust IDs received from the browser.

Example:

A user from Company A must never access:

```text
/invoice/view/500
```

if invoice 500 belongs to Company B.

---

# 28. IDOR PROTECTION

This is mandatory.

Never do:

```php
SELECT * FROM invoices WHERE id = ?
```

only.

Use company/access checks:

```sql
SELECT *
FROM invoices
WHERE id = ?
AND company_id = ?
```

For role-specific access, apply additional authorization.

---

# 29. FILE UPLOAD SECURITY

Uploaded files must be secure.

Requirements:

* Validate MIME type.
* Validate extension.
* Generate random storage filename.
* Limit file size.
* Do not trust client MIME.
* Store private documents outside public directory where possible.
* Block executable files.
* Prevent PHP execution inside upload directories.
* Never allow `.php`, `.phtml`, `.phar`, `.cgi`, etc.
* Sanitize download headers.
* Use access control before downloading.

---

# 30. DATABASE SECURITY

Use:

```text
utf8mb4
InnoDB
foreign keys where appropriate
indexes
unique constraints
```

Add indexes to:

```text
company_id
customer_id
project_id
invoice_id
payment_id
created_at
status
phone
email
```

Use transactions for financial operations.

Example:

```text
Invoice creation
Payment creation
Payment deletion
Share sale
Share cancellation
```

must use database transactions.

---

# 31. MONEY / FINANCIAL PRECISION

Do not use floating-point arithmetic for financial calculations.

Use MySQL:

```text
DECIMAL(15,2)
```

or appropriate precision.

PHP calculations should also be handled carefully.

All amounts must be validated server-side.

---

# 32. SOFT DELETE

For important business records, prefer soft delete where appropriate.

Examples:

```text
customers
leads
invoices
payments
employees
expenses
projects
```

Do not permanently delete financial records unless the user has a special permission.

For payments and invoices, cancellation/reversal is preferred over physical deletion.

---

# 33. NUMBER GENERATION

Create reusable number generator.

Examples:

```text
CUS-000001
LEAD-000001
INV-2026-000001
PAY-2026-000001
EXP-2026-000001
EMP-000001
```

Make prefixes configurable.

Number generation must be safe against duplicate numbers during concurrent requests.

Use database transactions/locking where required.

---

# 34. UI / UX

Create a modern lightweight admin panel.

Layout:

```text
Sidebar
Top Navbar
Breadcrumb
Page Content
Footer
```

Responsive:

```text
Desktop
Tablet
Mobile
```

Mobile must be properly usable.

Main menu:

```text
Dashboard

CRM
 ├── Leads
 ├── Customers
 ├── Activities
 └── Offers

Projects
 ├── Projects
 ├── Shares
 └── Gallery

Sales
 ├── Sales
 ├── Invoices
 ├── Payments
 └── Receipts

HR
 ├── Employees
 ├── Departments
 ├── Designations
 └── Salary

Finance
 ├── Expenses
 └── Reports

Reports

Settings
 ├── Company
 ├── Invoice
 ├── Payment
 ├── Salary
 ├── Expense
 ├── Master Data
 ├── Users
 ├── Roles
 └── Permissions

System
 └── Audit Logs
```

Menu visibility should depend on permissions.

---

# 35. AJAX

Use Fetch API for:

* Search
* Filtering
* Status updates
* Dynamic dropdowns
* Payment calculations
* Customer lookup
* Share availability
* Dashboard widgets

Do not turn the entire application into a complicated SPA.

Prefer server-rendered HTML with AJAX where useful.

This keeps the system lightweight.

---

# 36. API-READY ARCHITECTURE

Even if the first version does not expose a public API, design services/controllers so an API can be added later.

Business logic should not be tightly coupled to HTML views.

For example:

```text
InvoiceService
PaymentService
CustomerService
ShareService
OfferService
```

This will allow future:

```text
REST API
Mobile App
Customer Portal
```

---

# 37. CUSTOMER PORTAL READY

Architect the system so a customer portal can be added later.

Potential future features:

```text
Customer Login
Profile
Purchased Shares
Invoices
Payments
Due Amount
Payment History
Documents
Offers
Receipts
Project Gallery
```

Do not necessarily build the full portal in V1 unless required, but structure the database/services accordingly.

---

# 38. NOTIFICATION ARCHITECTURE

Create a notification service abstraction.

Future channels:

```text
Email
SMS
WhatsApp
System Notification
```

For V1, implement only what can be done reliably without creating unnecessary dependencies.

Do not hard-code one SMS provider.

Use configurable providers later.

---

# 39. ERROR HANDLING

Never expose PHP errors to users in production.

Create:

```text
application logs
error logs
security logs
```

User sees:

```text
Something went wrong. Please try again.
```

Developer logs contain technical details.

---

# 40. CONFIGURATION

Create `.env.example`.

Example:

```env
APP_NAME="Lake View Iconic Tower CRM"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost/lake-view-crm

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lake_view_crm
DB_USERNAME=root
DB_PASSWORD=

SESSION_NAME=lakecrm
```

Never commit actual credentials.

`.env` must be included in `.gitignore`.

---

# 41. INSTALLATION SYSTEM

Create a simple installation process if practical.

Example:

```text
/install
```

Installation should:

1. Check PHP version.
2. Check required PHP extensions.
3. Check MySQL connection.
4. Create database tables.
5. Create initial company.
6. Create Super Admin.
7. Create default roles.
8. Create default permissions.
9. Create default master data.
10. Lock/disable installer after installation.

The installer must not be accessible after successful installation.

---

# 42. DATABASE DESIGN

Create a proper normalized MySQL database.

At minimum consider:

```text
companies
users
roles
permissions
role_permissions
user_roles

projects
share_types
share_packages
share_transactions

leads
lead_statuses
lead_sources

customers
customer_documents

offers
offer_items

invoices
invoice_items

payments
payment_methods

employees
departments
designations
salary_components
employee_salary
salary_payments

expenses
expense_categories

documents
gallery_items

activities
audit_logs

settings
master_data
notifications
```

Do not blindly create every table listed above if a better normalized architecture exists.

Use foreign keys and indexes appropriately.

---

# 43. DATABASE MIGRATION

Create SQL migration files or an installation schema.

Example:

```text
database/migrations/
001_create_companies.sql
002_create_users.sql
003_create_roles.sql
...
```

Make it easy to install on a new server.

Also create:

```text
database/seeders/
```

for default:

* roles
* permissions
* payment methods
* expense categories
* lead statuses
* lead sources

---

# 44. BACKUP

Create a simple database backup utility for authorized administrators.

Requirements:

* Admin permission required.
* Export database SQL.
* Never expose backup publicly.
* Store outside public web root if possible.
* Log backup activity.

---

# 45. SECURITY HEADERS

Where possible configure:

```text
X-Content-Type-Options
X-Frame-Options
Referrer-Policy
Content-Security-Policy
Permissions-Policy
```

Do not introduce a CSP that breaks the application unnecessarily.

Prefer a reasonable secure policy.

---

# 46. .HTACCESS

If Apache is used, configure:

* Front controller routing
* Disable directory listing
* Protect `.env`
* Protect configuration files
* Protect storage
* Prevent PHP execution in uploads
* HTTPS redirect option

Do not assume Nginx is unavailable; document both Apache and Nginx considerations.

---

# 47. CODE QUALITY

Write clean PHP.

Use:

```php
declare(strict_types=1);
```

where practical.

Use:

```text
PSR-4 style autoloading
namespaces
classes
interfaces where useful
```

Do not overuse OOP for trivial code.

Avoid:

```text
global variables
God classes
huge controllers
duplicate SQL
duplicate HTML
inline business logic
```

---

# 48. REUSABLE SERVICES

Create reusable services such as:

```text
AuthService
PermissionService
CompanyService
CustomerService
LeadService
OfferService
InvoiceService
PaymentService
ShareService
DocumentService
FileUploadService
NotificationService
AuditLogService
NumberGeneratorService
```

Use services for business logic.

---

# 49. SEARCH / FILTER / PAGINATION

All major listing pages should support:

```text
Search
Pagination
Sorting
Date filters
Status filters
```

Pagination must be server-side.

Do not load 100,000 records into the browser.

---

# 50. DATA VALIDATION

Create reusable validation.

Examples:

```text
required
email
phone
numeric
decimal
date
min
max
unique
```

Return user-friendly validation errors.

Never rely on HTML `required` only.

---

# 51. SECURITY TESTING

Before considering the project complete, test:

### Authentication

* Wrong password
* Brute force attempts
* Session fixation
* Logout
* Session timeout

### Authorization

* Unauthorized URL access
* Cross-company access
* Role permission bypass
* IDOR

### Input

* SQL injection
* XSS
* CSRF
* Malicious filenames
* Invalid IDs

### Files

* PHP upload
* Double extensions
* MIME spoofing
* Path traversal

### Financial

* Negative payment
* Excessive payment
* Duplicate payment
* Concurrent payment
* Invoice manipulation
* Share quantity manipulation

---

# 52. IMPORTANT BUSINESS RULES

Implement these server-side.

### Share availability

User cannot sell more shares than available.

Example:

```text
Available Shares = 100
Customer requests = 120

REJECT
```

### Payment

Cannot accept payment for a cancelled invoice.

### Payment amount

Cannot create invalid negative payment.

### Invoice

Invoice total must be calculated by server.

### Discount

Validate discount against allowed rules.

### Company isolation

A user cannot access another company's records unless explicitly authorized.

---

# 53. SUPER ADMIN

Super Admin should have:

```text
Full access
All companies
All projects
All users
All settings
All reports
Audit logs
Backup
System settings
```

But even Super Admin actions should be audited.

---

# 54. DEFAULT SEED DATA

After installation create:

### Roles

```text
Super Admin
Admin
Manager
Director
Sales Executive
Accountant
Employee
Customer
```

### Lead Status

```text
New
Contacted
Interested
Offer Sent
Follow Up
Negotiation
Converted
Lost
```

### Payment Methods

```text
Cash
Bank
bKash
Nagad
Rocket
Cheque
```

### Expense Categories

```text
Office Rent
Utilities
Marketing
Transport
Salary
Maintenance
Other
```

---

# 55. INITIAL COMPANY

Create an installation form:

```text
Company Name
Company Address
Phone
Email
Website
Logo
```

Default company:

```text
Lake View Iconic Tower
```

But make it editable from Company Setup.

---

# 56. DEFAULT ADMIN

Installation form:

```text
Admin Name
Email
Phone
Username
Password
Confirm Password
```

Create this user as:

```text
Super Admin
```

Password must be securely hashed.

---

# 57. CUSTOMER CODE

Automatically generate:

```text
CUS-000001
CUS-000002
CUS-000003
```

Lead:

```text
LEAD-000001
```

Employee:

```text
EMP-000001
```

Invoice:

```text
INV-2026-000001
```

Payment:

```text
PAY-2026-000001
```

Expense:

```text
EXP-2026-000001
```

All prefixes must be configurable.

---

# 58. PRINT DESIGN

Create professional printable layouts for:

* Offer
* Invoice
* Payment receipt
* Customer statement
* Share purchase agreement
* Expense voucher

The print templates should contain:

```text
Company Logo
Company Name
Address
Phone
Email
Website
Document Number
Date
Customer
Project
Financial details
Authorized signature
Terms
Footer
```

Do not hard-code company information into templates.

---

# 59. CUSTOMER STATEMENT

Create a customer statement page.

Show:

```text
Opening Balance
Invoices
Payments
Adjustments
Current Due
```

Filter by:

```text
Customer
Project
Date range
```

---

# 60. FINANCIAL SUMMARY

Dashboard/report should calculate:

```text
Total Sales
Total Collection
Total Due
Total Expense
Net Collection
```

Be careful not to call revenue/profit incorrectly.

Use appropriate terminology.

---

# 61. RESPONSIVE TABLES

Large tables should work on mobile.

Use:

* Responsive table
* Horizontal scroll where appropriate
* Compact columns
* Action dropdowns
* Mobile-friendly forms

---

# 62. CONFIRMATION FOR DANGEROUS ACTIONS

Before:

```text
Delete
Cancel Invoice
Cancel Payment
Deactivate User
Delete Document
```

show confirmation.

But confirmation is only UX.

Authorization must still happen server-side.

---

# 63. NO TRUST IN JAVASCRIPT

This is extremely important.

JavaScript may calculate:

```text
subtotal
discount
total
due
```

for user experience.

But PHP must recalculate everything before saving.

Example:

```text
Browser says total = 50,000

Server calculates total = 70,000

Server value = 70,000
```

Never trust hidden fields.

---

# 64. PERFORMANCE

Keep the system lightweight.

Avoid:

* unnecessary libraries
* unnecessary API requests
* giant JavaScript bundles
* huge CSS frameworks
* loading all records
* N+1 database queries

Use:

```text
indexes
pagination
lazy loading
efficient SQL
```

---

# 65. FINAL DELIVERABLE

Build the complete project.

Deliver:

```text
Full source code
Database schema
Migration SQL
Seed data
Installation process
README
.env.example
Security documentation
Default credentials documentation
Deployment instructions
```

README must explain:

1. Requirements
2. Local installation
3. Database setup
4. Environment configuration
5. Apache setup
6. cPanel deployment
7. Nginx notes
8. File permissions
9. Cron jobs if required
10. Backup
11. Security configuration
12. Creating the first Super Admin

---

# 66. DEVELOPMENT PROCESS

Do NOT attempt to blindly generate the entire application in one giant file or one giant response.

Work module by module.

Recommended order:

### Phase 1

Foundation:

```text
Project structure
Configuration
Database
Router
Autoloader
Helpers
Security
Authentication
Session
CSRF
RBAC
```

### Phase 2

Core:

```text
Companies
Users
Roles
Permissions
Settings
Master Data
Audit Logs
```

### Phase 3

CRM:

```text
Leads
Activities
Customers
Documents
Gallery
Offers
```

### Phase 4

Project/Sales:

```text
Projects
Share Types
Share Packages
Share Transactions
Invoices
Payments
Receipts
```

### Phase 5

HR/Finance:

```text
Employees
Departments
Designations
Salary
Expenses
```

### Phase 6

Reports:

```text
Sales
Collection
Due
Expenses
Leads
Customers
Shares
Employee
Financial Summary
```

### Phase 7

UI/UX:

```text
Dashboard
Responsive layouts
Print templates
Mobile optimization
```

### Phase 8

Security testing and optimization.

---

# 67. CLAUDE CODE WORKFLOW

When working on the project:

1. First inspect the current directory.
2. If an existing project exists, do NOT overwrite it blindly.
3. Create a development plan.
4. Build the foundation first.
5. Create the database schema.
6. Implement authentication.
7. Implement RBAC.
8. Implement company isolation.
9. Implement modules one by one.
10. Test each module before moving to the next.
11. Run PHP syntax checks.
12. Review SQL queries.
13. Review authorization.
14. Review file upload security.
15. Review financial calculations.
16. Fix errors before proceeding.

After each major phase provide:

```text
Completed
Files changed
Database changes
Security considerations
Testing performed
Remaining work
```

---

# 68. CODE REVIEW REQUIREMENT

Before declaring the project complete, perform a security-focused code review.

Look specifically for:

```text
SQL Injection
XSS
CSRF
IDOR
Broken Access Control
Session Fixation
Authentication bypass
File upload vulnerabilities
Path traversal
Mass assignment
Sensitive data exposure
Insecure direct object references
Race conditions
Financial manipulation
Cross-company data leakage
```

Fix every issue found.

---

# 69. IMPORTANT: DO NOT OVER-ENGINEER

This is a lightweight CRM.

Do NOT turn it into:

```text
Laravel clone
Full enterprise ERP
Microservices
React SPA
GraphQL system
Docker-only application
Kubernetes system
```

The target environment may be:

```text
PHP + MySQL + Apache/cPanel
```

The application must work comfortably in a normal shared hosting environment.

---

# 70. UI BRANDING

Application name:

**Lake View Iconic Tower CRM**

Use a professional real-estate/CRM visual style.

The UI should be:

```text
Clean
Modern
Professional
Fast
Responsive
Minimal
```

Use a configurable theme rather than hard-coding colors everywhere.

---

# 71. FUTURE FEATURES — KEEP ARCHITECTURE READY

Do not necessarily implement these now, but do not make the architecture prevent them:

```text
Customer Portal
Flutter Mobile App
REST API
WhatsApp Integration
SMS Gateway
Email Gateway
Online Payment Gateway
eSignature
Document Verification
Advanced Accounting
Commission Management
Share Transfer
Installment Management
Payment Schedule
Reminder System
Multi-language
Bangla language
Multi-currency
Multi-tenant SaaS
```

---

# 72. FINAL QUALITY STANDARD

The final application should feel like a professional custom CRM, not a demo.

A developer should be able to:

```text
Create company
Create users
Create roles
Create project
Configure shares
Create leads
Send offers
Convert leads to customers
Sell shares
Generate invoices
Receive payments
Print receipts
Manage employees
Manage expenses
Generate reports
Manage settings
Review audit logs
```

without modifying source code for normal business configuration.

The most important design principle is:

> **Business data and configuration should live in the database; application behavior and security should live in the code.**

Start by inspecting the project directory and then create the architecture and implementation plan before writing the application.