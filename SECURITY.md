# Security overview

This document describes how Lake View CRM protects data and what an operator must configure. Report vulnerabilities privately to the system administrator.

## Authentication

* Passwords hashed with `password_hash(PASSWORD_DEFAULT)` (bcrypt/argon), transparently re-hashed on login when the algorithm changes. Minimum 8 characters with letters and numbers.
* Login by email, username or phone (`LOGIN_FIELDS`). Identical error for unknown user / wrong password, with a dummy hash check to equalise timing.
* **Brute-force throttling:** 5 failures per identifier+IP or 25 per IP within 15 minutes → locked for 15 minutes. All attempts are stored (`login_attempts`) and shown as login history.
* **Sessions:** `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS, strict mode (no uninitialised IDs), 48-char IDs, ID regenerated on login and password change, idle timeout (default 30 min) and absolute timeout (default 12 h), destroyed on logout, bound to the user-agent fingerprint.
* **Session revocation:** each user has a `session_version`; deactivating a user, resetting/changing a password or changing roles invalidates their other sessions immediately.
* Forgot-password tokens: 256-bit random, only the SHA-256 hash is stored, single use, 60-minute expiry, no account enumeration.

## Authorisation (RBAC) & company isolation

* Roles and role permissions are database-driven; the permission catalogue is code-controlled (`app/config/permissions.php`).
* Every controller action calls `authorize('module.action')` server-side; menus are hidden by permission but are never the only protection.
* **Multi-company isolation:** every business table has `company_id`; records are always loaded with `WHERE id = ? AND company_id = ?` (`Tenant::findOrFail`, `Tenant::owns`). IDs posted from forms (customer, project, package, status, method, user…) are validated as belonging to the active company. A user from company A requesting `/invoices/500` of company B gets **404**.
* Record-level scope: without `leads.view_all` users only see leads assigned to / created by them (also applied to lead documents, offers and activities).
* Privilege escalation guards: only a Super Admin can grant the Super Admin role, edit a Super Admin, edit global system roles, manage companies or download backups (backups contain all companies). Users cannot deactivate/delete themselves or drop their own Super Admin role.

## Input handling

* **SQL injection:** PDO with real prepared statements (`ATTR_EMULATE_PREPARES=false`) everywhere; identifiers pass a strict whitelist regex; sort columns come from per-page whitelists; LIKE wildcards are escaped.
* **XSS:** all output goes through `e()` (`htmlspecialchars`, `ENT_QUOTES`, UTF-8). JSON for charts is embedded with `JSON_HEX_*` flags in non-executable `application/json` script tags.
* **CSRF:** a per-session token is verified centrally for **every POST** (form field `_token` or `X-CSRF-Token` header). There are no state-changing GET routes.
* **Mass assignment:** the validator returns only declared fields; services never write raw `$_POST`.
* **Validation:** server-side for everything (required, email, phone, money, date, ranges, ownership…); HTML attributes are only UX.
* **CSV export:** values starting with `= + - @` are neutralised (formula injection).
* **Open redirects:** post-login redirect accepts only internal relative paths; `back()` only follows same-origin referrers.

## Money & business rules (all server-side)

* Amounts stored as `DECIMAL(15,2)`; PHP arithmetic uses integer paisa (`App\Core\Money`) — no floats.
* Totals sent by the browser are ignored; prices, fees, discounts, tax and grand totals are recalculated by `PricingService` / `InvoiceService`.
* Discounts limited to a configurable % of the subtotal unless the user holds `sales.discount`.
* **Overselling is impossible:** the project row is locked (`SELECT … FOR UPDATE`) while availability is checked and the sale inserted (tested with 10 parallel requests).
* Payments: must be > 0, ≤ invoice due, not for draft/cancelled invoices; invoice row locked; idempotency key prevents double submits; payments are reversed (cancelled), never deleted, and invoices/sales recalculated from confirmed payments.
* Issued invoices are immutable (cancel & re-issue); sales with payments cannot be cancelled until payments are reversed.
* Document numbers are generated under a row lock and skip already-used numbers (no duplicates under concurrency).
* All financial operations run inside database transactions.

## Files

* Uploads: extension whitelist + server-side MIME sniffing (`finfo`, client MIME ignored), double/executable extensions rejected (`.php`, `.phtml`, `.phar`, `.svg`, `.html` …), size limits, Office files verified as real OOXML archives, images **re-encoded with GD** (strips metadata and polyglot payloads) and downscaled.
* Stored under `storage/private/{company}/{yyyy}/{mm}/{random32}.{ext}` — outside the web root; original names are never used on disk.
* Downloads only through controllers that check authentication → permission → company ownership → record visibility, then stream with `nosniff`, a sandboxing CSP and RFC 5987 sanitised filenames. Path traversal is impossible (strict stored-name regex).
* `.htaccess` in every non-public folder denies access and disables PHP execution; only `public/index.php` may execute.

## Public share links

Project gallery, shareable documents and offers can be shared with prospects using `/share/{kind}/{id}/{expires}/{signature}`. The signature is an HMAC-SHA256 over kind/id/expiry using `APP_KEY`; links expire (default 14–30 days), can't be enumerated, and documents must be explicitly marked *shareable* (un-marking revokes them). Rotating `APP_KEY` revokes all links.

## Transport & headers

`Content-Security-Policy` (scripts from self only, no inline scripts), `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`, HSTS when served over HTTPS, `X-Powered-By` removed.

## Errors, logging & audit

* Production shows only "Something went wrong. Please try again." Technical details go to `storage/logs/error-*.log`.
* `security-*.log`: failed logins, throttling, CSRF failures, permission denials, rejected uploads.
* `audit_logs`: who did what, when, from which IP/user agent, with before/after values for changed fields (create/update/delete/cancel of customers, leads, invoices, payments, sales, settings, roles, users, logins/logouts, downloads, backups…). Passwords, tokens and NID image paths are never logged. Super Admin actions are audited too.

## Security testing performed

Automated HTTP tests were run against a live instance for: wrong password & brute force lockout, session-ID rotation on login, logout, CSRF missing/invalid, unauthorised URL access per role, cross-company IDOR on invoices/payments/sales/customers/leads/offers/projects/files/statements/API, role-permission bypass & self-escalation to Super Admin, system-role tampering, negative/zero/excessive/duplicate payments, payments on cancelled invoices, client-side total & fee manipulation, discount-limit bypass, share overselling under 10 concurrent requests, number-sequence uniqueness under concurrency, tampered/expired share-link signatures, backup path traversal, installer re-run after installation, and direct access to `.env`, `app/`, `storage/`, `database/` and `cli.php`.
