# Default seed data

Seed data is defined once in code and applied by the installer (and whenever a Super Admin creates a new company), so there is a single source of truth:

| Data | Defined in | Applied by |
|---|---|---|
| Permissions | `app/config/permissions.php` → `modules` | `RoleService::syncPermissions()` · `php cli.php sync-permissions` |
| Default roles (Super Admin, Admin, Manager, Director, Share Holder, Sales Executive, Accountant, Employee, Customer) and their permissions | `app/config/permissions.php` → `default_roles` | `RoleService::seedDefaultRoles()` (installer) |
| Lead statuses (New, Contacted, Interested, Offer Sent, Follow Up, Negotiation, Converted, Lost), lead sources, customer types, project statuses, share types, payment methods (Cash, Bank, bKash, Nagad, Rocket, Cheque), expense categories (Office Rent, Utilities, Marketing, Transport, Salary, Maintenance, Other), departments, designations, document types, activity types | `MasterDataService::SEED` | `CompanyService::seedDefaults()` per company |
| Salary components (House Rent 40 %, Transport, Medical, Mobile Bill, Festival Bonus, Sales Commission, Tax, Other Deduction) | `SalaryService::SEED_COMPONENTS` | `CompanyService::seedDefaults()` |
| Number sequences (CUS-, LEAD-, EMP-, PRJ-, OFF-YYYY-, SALE-YYYY-, INV-YYYY-, PAY-YYYY-, EXP-YYYY-, SAL-YYYY-) | `NumberGeneratorService::SEQUENCES` | `CompanyService::seedDefaults()` |

All seeders use `INSERT IGNORE` / upserts, so re-running them never duplicates or overwrites data a company has customised. Items flagged *system* (e.g. lead status "Converted", activity type "Payment") are used by the application logic: they can be renamed or recoloured but not deleted.

There are **no default user credentials** — the Super Admin is created in the installer with the password you choose.
