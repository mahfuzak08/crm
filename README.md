# Lake View Iconic Tower CRM

A lightweight, secure, multi-company CRM for **share-based land / real-estate projects** — leads, customers, offers, share sales, invoices, payments & receipts, HR/salary, expenses, reports and audit logs.

Plain **PHP 8.2 + MySQL/MariaDB**, Bootstrap 5 (served locally) and ~15 KB of vanilla JS. No framework, no Composer packages, no build step — runs on ordinary shared hosting / cPanel.

> Business data and configuration live in the database; application behaviour and security live in the code.

---

## 1. Requirements

| | |
|---|---|
| PHP | 8.2+ with `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `json`, `gd`, `zlib` (and `zip` recommended for .docx/.xlsx validation) |
| Database | MySQL 8+ or MariaDB 10.6+ (InnoDB, utf8mb4) |
| Web server | Apache 2.4 with `mod_rewrite` (recommended) or Nginx + PHP-FPM |
| Browser | Any modern browser; fully responsive (desktop / tablet / mobile) |

## 2. Local installation

```bash
# 1. Put the project in your web root (example: /var/www/html/crm)
# 2. Make storage writable by the web server user (see §8)
# 3. Open the installer
http://localhost/crm/install
```

The installer:

1. checks PHP version, extensions and folder permissions;
2. tests the MySQL connection and creates the database if it does not exist;
3. writes `.env` (or shows the content to paste if the folder is not writable);
4. runs all migrations (`database/migrations/*.sql`);
5. creates the first company, the **Super Admin**, default roles, permissions and master data;
6. writes `storage/installed.lock` — after that `/install` returns **404** for good.

## 3. Database setup

Either let the installer create the database (the DB user needs `CREATE` rights), or create it yourself:

```sql
CREATE DATABASE lake_view_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'lakecrm'@'localhost' IDENTIFIED BY 'a-strong-password';
GRANT ALL PRIVILEGES ON lake_view_crm.* TO 'lakecrm'@'localhost';
```

Schema lives in `database/migrations/` (applied in order and tracked in `schema_migrations`). After pulling new code run:

```bash
php cli.php migrate
php cli.php sync-permissions
```

Default data (roles, permissions, lead statuses/sources, payment methods, expense categories, departments, salary components…) is described in `database/seeders/README.md`.

## 4. Environment configuration

Copy `.env.example` to `.env` (the installer does this for you):

| Key | Purpose |
|---|---|
| `APP_ENV`, `APP_DEBUG` | Use `production` / `false` on live servers (errors are logged, never shown) |
| `APP_URL` | Full public URL without trailing slash, e.g. `https://crm.example.com` |
| `APP_KEY` | 64-hex secret used to sign public share links. Changing it invalidates all shared links |
| `DB_*` | Database connection |
| `SESSION_NAME`, `SESSION_IDLE_MINUTES`, `SESSION_MAX_HOURS` | Session cookie name, idle and absolute timeout |
| `LOGIN_FIELDS` | Which identifiers may be used to sign in: `email,username,phone` |
| `UPLOAD_MAX_MB` | Max upload size (also raise PHP `upload_max_filesize` / `post_max_size`) |
| `MAIL_DRIVER` | `none`, `mail` (PHP mail()) or `smtp` + `MAIL_HOST/PORT/USERNAME/PASSWORD/ENCRYPTION/FROM` |
| `FORCE_HTTPS` | Redirect HTTP → HTTPS from PHP (alternatively use the commented rule in `.htaccess`) |

`.env` is in `.gitignore` and blocked by `.htaccess`. Never commit real credentials.

## 5. Apache setup

Nothing extra is needed when the project folder is inside the document root: the root `.htaccess` sends every request into `public/`, so `app/`, `storage/`, `database/`, `.env` etc. can never be served. Requirements: `AllowOverride All` and `mod_rewrite` enabled.

Best practice for a dedicated (sub)domain — point the vhost straight at `public/`:

```apache
<VirtualHost *:443>
    ServerName crm.example.com
    DocumentRoot /var/www/crm/public
    <Directory /var/www/crm/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

To force HTTPS, uncomment the two `RewriteCond/RewriteRule` lines at the top of the root `.htaccess` (or set `FORCE_HTTPS=true`).

## 6. cPanel deployment

1. Upload the project (zip → *File Manager → Extract*) to e.g. `/home/USER/crm`.
2. **Recommended:** create a subdomain `crm.example.com` with document root `/home/USER/crm/public`.
   *Alternative:* upload into `public_html/crm` — the root `.htaccess` protects everything outside `public/`.
3. *MySQL® Databases*: create the database + user and grant all privileges.
4. *MultiPHP Manager*: select PHP 8.2+. *Select PHP Extensions*: enable those listed in §1.
5. Set folder permissions (§8), then open `https://crm.example.com/install`.
6. Add the cron jobs from §9.

## 7. Nginx notes

```nginx
server {
    server_name crm.example.com;
    root /var/www/crm/public;           # only public/ is exposed
    index index.php;
    client_max_body_size 20m;

    location / { try_files $uri /index.php?$query_string; }

    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
    location ~ \.php$ { return 404; }   # no other PHP may execute
    location ~ /\.(?!well-known) { deny all; }
    location ~* \.(css|js|woff2?|svg|png|jpe?g|webp)$ { expires 30d; access_log off; }
}
```

With Nginx `.htaccess` files are ignored, so pointing `root` at `public/` is **mandatory**.

## 8. File permissions

```bash
# owner = your deploy user, group = web server group (www-data / apache / nobody)
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 640 .env
chgrp -R www-data storage && chmod -R 770 storage   # logs, private uploads, backups
```

Only `storage/` (and `.env` during installation) must be writable. On cPanel (suPHP / PHP-FPM as your user) `755/644` is enough.

## 9. Cron jobs

```cron
# daily maintenance: overdue invoices, expired offers, prune old login records
15 1 * * *  php /path/to/crm/cli.php cron   >> /path/to/crm/storage/logs/cron.log 2>&1
# daily database backup, keep the newest 14
30 1 * * *  php /path/to/crm/cli.php backup keep=14 >> /path/to/crm/storage/logs/cron.log 2>&1
```

Run cron as the same user as the web server (default on cPanel) so backups remain downloadable from the UI. Overdue status is also refreshed automatically whenever invoices are listed, so cron is recommended but not required.

## 10. Backup

* **UI:** *System → Backup* (Super Admin only) creates a gzip SQL dump in `storage/backups/` (outside the web root), lists and downloads backups. Every create/download/delete is audit-logged.
* **CLI:** `php cli.php backup keep=14`.
* **Restore:** `gunzip -c backup-YYYYMMDD-HHMMSS-xxxx.sql.gz | mysql -u USER -p lake_view_crm`
* Also back up `storage/private/` (uploaded documents/photos) and `.env`.

Backups contain **all companies' data**, which is why they are restricted to the Super Admin.

## 11. Security configuration

See **[SECURITY.md](SECURITY.md)** for the full list. Production checklist:

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] HTTPS enabled and forced; session cookie becomes `Secure` automatically
- [ ] Document root points at `public/` (or root `.htaccess` active)
- [ ] `storage/` writable only by the web user; `.env` is `640`
- [ ] `APP_KEY` set (64 random hex characters)
- [ ] Strong unique Super Admin password; additional admins get **Admin** role, not Super Admin
- [ ] PHP `expose_php=Off`, `upload_max_filesize` ≈ `UPLOAD_MAX_MB`
- [ ] Cron backup configured and backups copied off-server

## 12. Creating the first Super Admin

The installer creates it (company name defaults to **Lake View Iconic Tower**; editable later in *Settings → Company*). There are **no built-in default credentials** — you choose username & password during installation (min 8 chars, letters + numbers). Sign in with email, username or phone.

Additional Super Admins: assign the *Super Admin* role in *Settings → Users* (only a Super Admin can), or from the shell:

```bash
php cli.php create-admin
```

Forgot the only password? Run `php cli.php create-admin`, sign in, and reset the old account under *Settings → Users*.

---

## Using the CRM

Step-by-step instructions for end users are in **[docs/USER_GUIDE.md](docs/USER_GUIDE.md)**.

| Area | What you can do |
|---|---|
| **Dashboard** | KPIs (leads, customers, shares, sales, collection, due, expense, employees), monthly charts, follow-ups, overdue invoices. Super Admin can switch company or view all companies |
| **CRM** | Leads with dynamic statuses/sources, assignment, follow-ups, activity timeline (call, meeting, WhatsApp…), one-click WhatsApp / email / gallery link, convert to customer. Customers with NID/photos (private), documents, statement |
| **Projects & Shares** | Projects with land/share configuration, share packages, live inventory (total / sold / reserved / available / cancelled) and an immutable movement ledger. Project gallery (images, documents, YouTube/Vimeo or MP4) with an expiring public share link |
| **Offers** | Server-priced offers for leads or customers, email/WhatsApp sending, public view link (marks *Viewed*), print/PDF, convert to sale |
| **Sales** | Reserve or confirm share sales (oversell-proof), automatic invoice, optional initial payment, cancellation (releases shares), purchase agreement print |
| **Invoices & payments** | Manual invoices with line items/tax, draft → issue, configurable numbering, payments with receipts, reversal instead of deletion, customer statement |
| **HR & Finance** | Employees, departments, designations, dynamic salary components and per-employee structure, monthly salary + payslip, expenses with approval workflow & vouchers |
| **Reports** | Sales, collection, due (ageing), expense, lead (conversion), customer, share inventory, employee, financial summary — filter, search, paginate, print, CSV |
| **Settings** | Company profile/logo/theme colour/currency/timezone, invoice & numbering, sales/offer rules (discount limit, fee override, auto-invoice), master data, users, roles & permissions, companies |
| **System** | Audit log (who changed what, before/after), database backup |

Printing: every document page has *Print / Save as PDF* (use the browser's "Save as PDF").

## Project structure

```
app/
  Core/          Router, Database, Query builder, Validator, Session, Csrf, Response, Money, View …
  Controllers/   Thin HTTP controllers (permission check → service → view)
  Services/      Business logic (API-ready): Auth, Tenant, Lead, Customer, Offer, Sale, Invoice, Payment, Share, …
  Middleware/    auth / guest / can:permission / super
  Helpers/       e(), url(), money(), csrf_field(), form_* builders …
  config/        app.php (infrastructure), permissions.php (RBAC catalog), menu.php
bootstrap/app.php  autoloader, env, error handling
public/          index.php (only PHP entry), assets (Bootstrap, icons, app.css, app.js)
routes/          web.php, install.php
views/           PHP templates (layouts, partials, modules, print templates)
database/        migrations/*.sql, seeders/README.md
storage/         logs/, private/ (uploads), backups/   — never web-accessible
cli.php          migrate | sync-permissions | cron | backup | create-admin
```

### Extending

* **New permission:** add it to `app/config/permissions.php`, run `php cli.php sync-permissions`, tick it on roles.
* **New lookup list:** add a type to `MasterDataService::TYPES` — the setup UI appears automatically.
* **REST API / mobile app / customer portal:** controllers are thin; reuse the services (they validate, authorise company ownership and return plain arrays). `customers.user_id` is reserved for portal logins.
* **SMS / WhatsApp gateway:** implement `App\Services\Notifications\Channel` and register it in `NotificationService::channels()`.

## Logs

`storage/logs/app-*.log`, `error-*.log` (technical details, never shown to users), `security-*.log` (failed logins, CSRF failures, permission denials, rejected uploads). JSON lines, one file per day.
