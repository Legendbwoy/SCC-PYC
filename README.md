# St. Cecilia Catholic Church — Parish Youth Council (`SCC-PYC`)

Secure, auditable online management platform for **St. Cecilia Catholic Church Parish Youth Council**, implementing **PYC Complete Technical Specification (Version 1.2)** on Laravel 12, MySQL/MariaDB, Blade, Livewire, Tailwind CSS, Chart.js, DomPDF, Laravel Fortify (with TOTP 2FA), and Spatie Laravel Permission.

## Implemented Modules

- **Foundation, Security, RBAC & 2FA (Milestone 1)**:
  - 7 PYC roles (`Super Administrator`, `PYC President`, `Treasurer`, `Financial Secretary`, `General Secretary`, `Auditor`, `Member`) and 44 granular permissions with zero wildcard bypass on financial controls.
  - Mandatory TOTP 2FA (`EnsureAdministratorTwoFactor`) for all executive/administrative roles, invitation-only member onboarding, append-only `AuditLog` with secret redaction, non-secret `SystemSetting`, integer-pesewas `Money` VO, and row-locked `ReferenceGenerator` (`PYC-YYYY-NNNN`, `PYC-PAY-*`, `PYC-RCT-*`, `PYC-JRN-*`, `PYC-EXP-*`, `PYC-EVT-*`, `PYC-REF-*`).
- **SMS, Email & In-App Notifications (Milestone N1)**:
  - Provider-independent `GenericHttpSmsGateway`, `SmtpEmailProvider`, database portal alerts, 18 versioned trigger templates (36 SMS + Email templates), GSM-7/UCS-2 segment estimator, mandatory vs optional consent separation, 5-step exponential retry queues, and HMAC-verified delivery webhooks.
- **Membership & Google Forms Integration (Sprint 2)**:
  - Google Sheets v4 sync (`pyc:sync-google-members` every 15m), `+233` phone/email normalization, non-destructive duplicate candidate flagging, Secretary approval/rejection, sequential `PYC-YYYY-NNNN` numbering, and SHA-256 hashed single-use portal invitations.
- **Member Portal & Contributions / Monthly Dues (Sprint 3 & 4)**:
  - Confirmed **GHS 10.00 (`1,000` pesewas)** Monthly Youth Dues (`pyc:generate-monthly-dues`), effective-dated campaigns, FIFO partial & automatic future-month advance payment allocations, audited waivers/exemptions, operational arrears tracking, and strict member portal isolation (`/my/profile`, `/my/dues`, `/my/payments`, `/my/events`).
- **Payments, Receipts, Double-Entry Accounting & Expenses (Sprint 5 & 6)**:
  - Paystack online checkout, server-side amount/currency verification, HMAC-SHA512 webhook deduplication, manual cash recording with strict `MakerCheckerGuard` dual authorization, Paystack bank settlement & refund postings, sole-authority `JournalPostingService` (`1000`–`5400` Chart of Accounts, immutable journals, traceable reversals, period closures), dual-authorized Expenses, and single-issuance DomPDF `PYC-RCT-*` official receipts.
- **Events, Registrations & Executive/Auditor Reports (Sprint 7)**:
  - Parish youth event publishing, capacity & attendance tracking, shared event contribution obligations (`4100`), interactive Chart.js analytics, Cash/Bank books, Trial Balance, Income & Expenditure, and user-attributed PDF & Excel-compatible CSV exports.

## Local Setup & Commands

```bash
# 1. Install dependencies & build frontend assets
composer install
npm install && npm run build

# 2. Run database migrations & seed roles, templates, chart of accounts, periods, and GHS 10 dues campaign
php artisan migrate --seed

# 3. Provision one-time initial Super Administrator (no shared default password)
php artisan pyc:provision-admin --name="PYC Super Administrator" --email="admin@stceciliapyc.org"

# 4. Run formatting check & full test suite
./vendor/bin/pint --test
php artisan test
```

See [ARCHITECTURE.md](ARCHITECTURE.md) for module boundaries and architectural invariants.
