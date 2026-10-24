# Lake View Iconic Tower CRM — User Guide

This guide is for the people who use the CRM every day: sales executives, accountants, managers, directors and administrators. It explains what each screen is for and walks through the common jobs step by step.

> Installation, server setup and backups for IT staff are covered in [README.md](../README.md).

---

## Contents

1. [Getting started](#1-getting-started)
2. [The screen layout](#2-the-screen-layout)
3. [Dashboard](#3-dashboard)
4. [Leads](#4-leads)
5. [Customers](#5-customers)
6. [Activities and follow-ups](#6-activities-and-follow-ups)
7. [Documents](#7-documents)
8. [Projects, share packages and inventory](#8-projects-share-packages-and-inventory)
9. [Gallery](#9-gallery)
10. [Offers](#10-offers)
11. [Share sales](#11-share-sales)
12. [Invoices](#12-invoices)
13. [Payments and receipts](#13-payments-and-receipts)
14. [Customer statement](#14-customer-statement)
15. [Employees and salary](#15-employees-and-salary)
16. [Expenses](#16-expenses)
17. [Reports](#17-reports)
18. [Settings (administrators)](#18-settings-administrators)
19. [Users, roles and permissions](#19-users-roles-and-permissions)
20. [System: audit log and backup](#20-system-audit-log-and-backup)
21. [Everyday workflow cheat sheet](#21-everyday-workflow-cheat-sheet)
22. [Troubleshooting and FAQ](#22-troubleshooting-and-faq)

---

## 1. Getting started

### Signing in

Open the CRM address your administrator gave you (for example `https://crm.example.com`) and sign in with your **email, username or phone number** and your password.

- After **5 wrong passwords** the account is locked for **15 minutes** from that device.
- You are signed out automatically after **30 minutes without activity**, and after **12 hours** in any case. Unsaved form data is lost, so save regularly.

### Forgot your password

Click **Forgot password?** on the sign-in page and enter your email. If email is configured, you receive a reset link that is valid for **60 minutes**. If you don't receive it, ask an administrator to reset your password (*Settings → Users*).

### Your profile

Click your name in the top-right corner:

- **My Profile** — update your name, email and phone, and see your recent sign-ins (date, IP address, device). If you see a sign-in you don't recognise, change your password immediately and tell an administrator.
- **Change Password** — at least 8 characters, with both letters and numbers.
- **Sign out** — always sign out on shared computers.

### What you can see depends on your role

Menus, buttons and records appear only if your role allows them. If you can't find something described in this guide, your role probably doesn't include it — ask an administrator (see [section 19](#19-users-roles-and-permissions)).

---

## 2. The screen layout

| Area | What it does |
|---|---|
| **Sidebar** (left) | Main menu, grouped into CRM, Projects, Sales, HR, Finance, Reports, Settings and System. Click a group to open it. The page you are on is highlighted. |
| **Top bar** | Global search, the company switcher (Super Admin only) and your user menu. |
| **Page header** | Breadcrumb (*Home / Section / Page*), page title, and the main buttons for that page (e.g. *New lead*, *Print / PDF*). |

**Search.** Type a name, phone number, customer code, invoice number etc. in the top search box to search across leads, customers, invoices and more.

**Lists.** Every list has filters (status, project, date range, assigned user…), a search box, sortable column headers and page numbers. Wide tables scroll sideways on small screens.

**On a phone.** The sidebar is hidden; tap the **☰** button in the top bar to open the menu, and tap outside it to close it. The company logo and name stay visible in the top bar.

**Dangerous actions** (delete, cancel, reverse) always ask for confirmation, and most ask for a **reason**, which is stored in the audit log.

---

## 3. Dashboard

The first page after signing in. It summarises the current company:

- **Cards:** total leads, new leads this month, customers, projects, available and sold shares, total sales, total invoiced, total collection, total due, total expense, employees. Click a card to open the related list.
- **Charts:** monthly sales, collection and expense (last 12 months), and lead conversion by status.
- **Follow-ups due** — leads whose next follow-up date has arrived.
- **Overdue invoices** — invoices past their due date that are not fully paid.
- **Notifications** — system notices relevant to you.

Quick buttons: **+ Lead** and **Sell shares**. A Super Admin can switch between *This company* and *All companies*.

---

## 4. Leads

*CRM → Leads.* A lead is a prospective buyer who has not purchased yet.

### Add a lead

1. Click **New lead**.
2. Fill in **Full name** and at least a **Phone** or **Email**. Optional: address, **Interested project**, **Source** (Facebook, Website, Referral, Walk-in…), **Status**, **Assigned to**, **Next follow-up** date and notes.
3. Click **Save lead**.

If another lead already has the same phone number, the CRM warns you and shows the existing lead code. Open that lead instead, or tick **Allow duplicate phone number** if it really is a different person.

### Work a lead

Open a lead to see its details, timeline, offers and documents. From there you can:

- **Change status** using the status dropdown. It saves immediately. The default pipeline is *New → Contacted → Interested → Offer Sent → Follow Up → Negotiation → Converted / Lost*.
- **Assign** the lead to a sales person (requires the *Assign leads* permission).
- **Call**, **WhatsApp** or **Email** with one tap. WhatsApp and email contacts are recorded on the timeline automatically.
- **Gallery link**: copies a link to the project gallery (valid 30 days) that you can paste into a message.
- **Log activity** (call, meeting, site visit, WhatsApp…) and set the next follow-up date — see [section 6](#6-activities-and-follow-ups).
- **Create offer**: prepares a price offer for this lead — see [section 10](#10-offers).
- **Upload documents** related to the lead.

Sales executives see only leads assigned to them or created by them. Managers and directors with *View all leads* see everyone's.

### Convert a lead to a customer

When the lead agrees to buy, click **Convert to customer** and confirm. The CRM:

- creates a customer record with a new customer code (e.g. `CUS-000123`),
- sets the lead status to **Converted**,
- moves the lead's documents to the customer,
- records the conversion on both timelines.

After conversion, use **View customer** on the lead.

---

## 5. Customers

*CRM → Customers.* A customer is someone who has bought, or is about to buy, shares.

### Add or edit a customer

Customers are usually created by converting a lead, but you can also click **New customer**. Only the name is required. Other fields include father's, mother's and spouse's names, phones, email, NID / passport, date of birth, occupation, address, customer type, photo, and NID front/back images.

- An NID number can belong to only one customer.
- Photos and NID images are **private**: they never have a public web address and can only be viewed by signed-in users with permission.

### The customer page

The customer page has tabs:

| Tab | Shows |
|---|---|
| **Shares** | Every share sale for this customer |
| **Invoices** | All invoices with paid and due amounts |
| **Payments** | Every payment, with a link to the receipt |
| **Offers** | Offers sent to this customer |
| **Timeline** | Calls, meetings, notes and system events |

Buttons: **Sell shares**, **Statement**, **Edit** and **View lead** (if the customer came from a lead).

---

## 6. Activities and follow-ups

Every lead and customer has a **timeline**. Use **Log activity** on the lead or customer page:

1. Choose the **type** (Call, Meeting, Site Visit, WhatsApp, Email, Note…).
2. Write what happened.
3. Optionally change the date and set the **Next follow-up** date.

Leads whose follow-up date is due appear under **Follow-ups due** on the dashboard.

*CRM → Activities* lists all activities, with filters for type, user and date.

The system adds its own timeline entries automatically, for example *Converted to customer*, *Purchased 5 shares…* and *Payment PAY-2026-000012 received*.

---

## 7. Documents

*CRM → Documents*, plus the **Documents** panel on lead, customer and project pages.

- **Upload:** choose the document type (Land Document, Deed / Dolil, Brochure, Agreement, NID…), a title and the file.
- **Allowed files:** PDF, Word (doc/docx), Excel (xls/xlsx), and JPG/PNG/WEBP images. The default size limit is 10 MB.
- **Download:** files are stored privately and are downloaded through the CRM after a permission check.
- **Share link:** the link button creates a link valid for **14 days**, which you can send to someone outside the CRM. After that it stops working.
- **Delete** requires the *Delete documents* permission.

---

## 8. Projects, share packages and inventory

*Projects → Projects.*

### Create a project

Click **New project** and fill in the name, code, location, description, total land area, **total shares**, share unit (how much land one share represents), share price, registration fee, development fee, status (Planning, Ongoing, Completed, On Hold) and dates.

You cannot reduce **Total shares** below the number of shares already sold or reserved.

### Share packages

On the project page, under **Share packages**, add bundles you sell, for example *"Standard — 5 shares — ৳ 25,00,000"*. Each package has a type (Standard, Premium, Corner Plot…), a share count, a package price and a status.

### Inventory

The project page shows live inventory: **Total, Sold, Reserved, Available, Cancelled**. *Projects → Shares* shows the same figures for all projects at once.

Every reservation, sale, release and cancellation is recorded in the project's **Share movements** list. This history cannot be edited.

The CRM never sells more shares than are available. If two people try to sell the last shares at the same moment, the second sale is refused with *"Only N shares are available"*.

---

## 9. Gallery

*Projects → Gallery.* Marketing material for each project:

- **Images** (JPG, PNG, WEBP)
- **Documents** (PDF, Word, Excel)
- **Videos**: paste a YouTube or Vimeo link, or upload an MP4/WEBM file

Each item has a title, description, sort order and status. Only *active* items are shown publicly.

**Copy public link** creates a read-only web page of the project gallery, valid for **30 days**, for sharing with prospects. The same link is available from a lead's **Gallery link** button.

---

## 10. Offers

*CRM → Offers.* An offer is a formal price quotation for a lead or a customer.

### Create an offer

1. Click **New offer**, or **Create offer** on a lead.
2. Choose the **recipient** (lead or customer), the **project**, and the package or number of shares.
3. Enter a discount if needed and check the fees. **Valid until** defaults to the number of days set in *Settings → Sales & Offers*.
4. Add notes or custom terms. If you leave terms blank, the company default is used.
5. Click **Save offer**. It is saved as **Draft**.

Prices and totals are always recalculated by the server, so the figures on the offer are always correct.

### Send and track

On the offer page:

- **Send** emails the offer (if email is configured) and marks it **Sent**.
- **Send via WhatsApp** opens WhatsApp with the offer link.
- **Copy offer link** copies a public view link. When the recipient opens it, the status changes to **Viewed**.
- **Print / PDF** opens a printable version; use your browser's *Save as PDF* to save it.
- Change the status to **Accepted** or **Rejected** as the conversation progresses.
- Offers past their *Valid until* date become **Expired** automatically.

**Convert to sale**: when the customer accepts, convert the offer. If the recipient is still a lead, convert the lead to a customer first. The new sale is linked to the offer, and the offer becomes **Converted**.

You can edit only offers that are Draft, Sent or Viewed.

---

## 11. Share sales

*Sales → Share Sales → New sale*, or **Sell shares** on the dashboard, a customer or a project.

### Create a sale

1. Choose the **customer** and the **project**, then a **package** or a number of shares. Available shares are shown as you choose.
2. Check the unit price, **discount** and fees (registration, development, other).
   - A discount above the company limit (*Settings → Sales & Offers*) needs the *Give discount above company limit* permission.
   - Fees can be changed only if the company allows it.
3. Choose the **Status**:
   - **Reserved** holds the shares for the customer. No invoice is created and no payment can be taken yet.
   - **Confirmed** is a completed sale. An invoice is created automatically (if enabled in settings).
4. For a confirmed sale you can record an **initial payment** at once: amount received, method and reference. A receipt is created.
5. Choose the **Sales person** and **Sale date**, add notes, and click **Save sale**.

The CRM calculates subtotal, discount, fees, grand total, paid and due amounts.

### After the sale

On the sale page:

- **Confirm sale** turns a reservation into a sale and creates the invoice.
- **Receive payment** takes a payment against the sale's invoice.
- **Invoice** and **Agreement** print the invoice or the share purchase agreement.
- **Cancel sale** returns the shares to inventory and cancels the invoice. You must give a reason. **Cancel (reverse) any payments first**; a sale with confirmed payments cannot be cancelled.

---

## 12. Invoices

*Sales → Invoices.* Share sales create their invoices automatically. Use **New invoice** for anything else (extra charges, services…).

### Create a manual invoice

1. Choose the customer, optionally a project, the **Invoice date** and the **Due date**. The due date defaults from settings.
2. Add **line items** (description, quantity, unit price). Use **Add line** for more rows.
3. Enter discount, tax % and other charges. Totals are calculated for you.
4. Choose:
   - **Save as draft**: can still be edited, has no invoice number yet, and cannot be paid.
   - **Issue**: gets its number (e.g. `INV-2026-000045`) and becomes payable.

### Invoice statuses

| Status | Meaning |
|---|---|
| Draft | Not issued yet; editable |
| Unpaid | Issued; nothing paid |
| Partial | Partly paid |
| Paid | Fully paid |
| Overdue | Due date passed and not fully paid (updated automatically) |
| Cancelled | Cancelled with a reason; not payable |

Only draft invoices can be edited. Before you can cancel an invoice that has payments, you must reverse those payments. An invoice created by a share sale is cancelled by cancelling the **sale**.

**Print / PDF** gives a professional printable invoice with your logo, terms and footer.

---

## 13. Payments and receipts

*Sales → Payments → Receive payment*, or **Receive payment** on an invoice or sale.

1. Choose the customer and the **open invoice** being paid.
2. Enter the **Amount** (it cannot be more than the invoice due), **Payment date** (not in the future), **Method** (Cash, Bank, bKash, Nagad, Rocket, Cheque…) and **Reference** (transaction ID, cheque number…).
3. Click **Receive payment**.

The invoice's paid and due amounts and its status update immediately. Each payment gets a number such as `PAY-2026-000012` and a printable **receipt** (*Sales → Receipts*, or **Print receipt** on the payment).

### Correcting a mistake

Payments are never deleted. If a payment was entered wrongly:

- A wrong **reference or note** can be corrected with **Edit reference / notes**.
- A wrong **amount, date or invoice** needs **Cancel (reverse)** with a reason. Reversing increases the invoice due again. Then enter the correct payment.

The reversed payment stays visible, crossed out, with who reversed it, when and why.

---

## 14. Customer statement

*Sales → Customer Statement*, or **Statement** on a customer page.

Choose a customer (and optionally a project) and click **Show**. The statement lists every invoice and payment in date order with a running balance. Use **Print / PDF** to give the customer a copy.

---

## 15. Employees and salary

### Employees

*HR → Employees → New employee.* Enter the name, contact details, **department**, **designation**, joining date, **basic salary**, status and photo. An employee can be linked to a CRM user account.

Under **Salary structure**, set this employee's amount for each salary component (House Rent, Transport, Medical, Tax…). Leave a component blank to use its default value.

Departments and designations are managed under *HR → Departments / Designations*.

### Salary components

*Settings → Salary Components.* Each component has a type (**Allowance, Deduction, Bonus, Commission**) and a default value, either a fixed amount or a percentage of basic salary (for example House Rent 40 %).

### Pay salary

*HR → Salary → Pay salary.*

1. Choose the **Employee** and **Salary month**. The structure is filled in automatically.
2. Add any **Additional bonus**, **Additional commission** or **Additional deduction** for this month.
3. Enter the payment date, method and reference, then click **Pay salary**.

An employee can be paid only once per month. Each payment has a printable **payslip**. Use **Cancel payment** to undo a mistake, then pay again.

---

## 16. Expenses

*Finance → Expenses → New expense.*

1. Enter the **Date**, **Category** (Office Rent, Utilities, Marketing…), **Amount**, **Payment method**, reference and description.
2. Optionally link it to a **project** and attach the **bill / voucher** (image or PDF).
3. Click **Save expense**.

### Approval

If *New expenses require approval* is switched on (*Settings → Sales & Offers*), expenses entered by staff without approval rights are **Pending** until someone with the *Approve expenses* permission clicks **Approve**. If an approved expense is later edited by someone without approval rights, it goes back to Pending.

Only **approved** expenses count in reports and the profit/loss figures. **Voucher** prints an expense voucher. **Cancel** (with a reason) removes an expense from the totals without deleting it.

*Finance → Profit / Loss* shows sales, collection, expenses and net cash flow for a period.

---

## 17. Reports

*Reports.* Each report has filters (date range, project, customer, user, status), search, sorting, page numbers, **Print** and **CSV export** (CSV export requires the *Export reports* permission).

| Report | Shows |
|---|---|
| Sales Report | Share sales with subtotal, discount, fees, total, paid, due and sales person |
| Collection Report | Payments received, by date, method and project |
| Due Report | Outstanding invoices with ageing (how long overdue) |
| Expense Report | Approved expenses by category |
| Lead Report | Leads by status, source and sales person; conversion |
| Customer Report | Customers with shares, invoiced, paid and due |
| Share Inventory Report | Total, sold, reserved and available shares per project |
| Employee Report | Employees and salary paid in the period |
| Financial Summary | Sales, collection, expenses and net cash flow |

---

## 18. Settings (administrators)

| Menu | What you set |
|---|---|
| **Company** | Company name, short code, address, phone, email, website, tax/BIN information, **logo**, **theme colour**, currency code and symbol, timezone, date format, invoice and receipt footers |
| **Invoice & Numbering** | Default due days, default tax/VAT %, print template, default terms; **number formats** for customers, leads, offers, sales, invoices, payments, expenses and salary (prefix, starting number, number length, yearly reset) |
| **Sales & Offers** | Max discount without approval (%), allow fee override on sales, auto-create invoice on confirmed sale, default offer validity (days), default offer terms, share purchase agreement terms, whether new expenses require approval |
| **Payment Methods** | The payment methods offered in payment, sale, salary and expense forms |
| **Salary Components** | See [section 15](#15-employees-and-salary) |
| **Expense Categories** | Categories offered in the expense form |
| **Master Data** | All other drop-down lists: lead statuses (with colours), lead sources, customer types, project statuses, share types, departments, designations, document types, activity types |
| **Users** / **Roles & Permissions** | See [section 19](#19-users-roles-and-permissions) |
| **Companies** | Super Admin only — add and edit companies |

**About master data:** each item has a name, sort order and an *Active* switch. Turn off items you no longer use instead of deleting them, so old records keep their labels. Some items are marked **system** (for example the lead status *Converted*, used by lead conversion): you can rename or recolour them but not delete them.

**Changing the theme colour** re-colours the whole CRM and printed documents for that company.

---

## 19. Users, roles and permissions

### Users

*Settings → Users → New user.* Enter the name, username, email, phone, password and **at least one role**. On a user's page you can:

- **Edit** details and roles.
- **Reset password**. The user is signed out everywhere.
- **Deactivate**. The user can no longer sign in; their history is kept.

You cannot give someone a permission you don't have yourself. Only a Super Admin can manage Super Admin accounts.

### Default roles

| Role | Typical use |
|---|---|
| **Super Admin** | Everything, in all companies, including companies and backups |
| **Admin** | Everything inside the company, except managing companies and backups |
| **Manager** | Leads, customers, projects, shares, offers, sales, invoices, payments, reports; views employees and expenses |
| **Director** | Read-only access to everything, plus reports and expense approval |
| **Sales Executive** | Own leads, customers, offers, sales and documents |
| **Accountant** | Invoices, payments, expenses, salary and reports |
| **Employee** | Dashboard, own leads, activities, projects, gallery |
| **Share Holder** | Dashboard, projects, shares, gallery (view only) |
| **Customer** | Reserved for a future customer portal |

### Customise a role

*Settings → Roles & Permissions.* Edit a role (or create a new one) and tick the permissions it should have. They are grouped by module, with **Select all** / **Clear** buttons. Changes apply on the user's next page load.

---

## 20. System: audit log and backup

**Audit Logs** (*System → Audit Logs*) record who did what and when for every important change: create, edit, delete, cancel, approve, sign-in, permission changes and backups. Each entry shows the old and new values and the IP address. Filter by user, action, module or date.

**Backup** (*System → Backup*, Super Admin only) creates a complete database backup, which you can then download or delete. Backups contain **all companies' data**, so keep downloaded copies safe. Your IT person should also schedule automatic daily backups (see README).

---

## 21. Everyday workflow cheat sheet

**New enquiry to completed sale**

1. *CRM → Leads → New lead*, then assign it to a sales person.
2. Log calls and visits on the lead and set the next follow-up date.
3. Send the **Gallery link** and documents by WhatsApp or email.
4. **Create offer**, then send it by WhatsApp or email.
5. When the customer agrees: **Convert to customer**.
6. **Sell shares**: choose **Reserved** to hold the shares, or **Confirmed** to complete the sale. An invoice is created on confirmation.
7. **Receive payment** for each instalment and print the **receipt**.
8. Print the **Agreement** and give the customer a **Statement** when asked.

**Month end**

1. *HR → Salary → Pay salary* for each employee and print the payslips.
2. Approve pending expenses.
3. Run the **Due Report**, **Collection Report** and **Financial Summary**, then print or export them.

---

## 22. Troubleshooting and FAQ

**I can't see a menu or button.**
Your role doesn't include that permission. Ask an administrator.

**"Too many failed attempts."**
Wait 15 minutes, or ask an administrator to reset your password.

**I was signed out while working.**
Sessions end after 30 minutes without activity. Sign in again and re-enter any unsaved data.

**"Only N shares are available."**
Someone else reserved or sold shares in the meantime. Reduce the quantity or choose another project or package.

**"Discount may not exceed X% of the subtotal without approval."**
Ask a manager with the *Give discount above company limit* permission to create the sale.

**"Issue the invoice before receiving payment."**
The invoice is still a draft. Open it and click **Issue**.

**"Amount exceeds the invoice due."**
The payment is larger than what is owed on that invoice. Split it across invoices or check the amount.

**"Payments can only be received on confirmed sales."**
The sale is still a reservation. Click **Confirm sale** first.

**I can't cancel a sale or invoice.**
It has confirmed payments. Open each payment, click **Cancel (reverse)**, then cancel the sale or invoice.

**"Salary for this month has already been paid."**
Open the existing salary payment. If it was wrong, cancel it and pay again.

**A shared link says "invalid or has expired".**
Gallery links last 30 days and document links 14 days. Generate a new link.

**How do I save something as a PDF?**
Click **Print / PDF** (or **Print receipt**, **Voucher**, **Payslip**, **Agreement**). In the print window, choose **Save as PDF** as the printer.

**Can I delete a payment or a sale?**
No. Financial records are cancelled or reversed with a reason, never deleted. This keeps totals and the audit trail correct.
