# St. Cecilia Catholic Church — Parish Youth Council (`SCC-PYC`)

Secure, auditable online management platform for **St. Cecilia Catholic Church Parish Youth Council**, built on Laravel 12, MySQL/MariaDB, Blade, Livewire, Tailwind CSS, Laravel Fortify (with TOTP 2FA), and Spatie Laravel Permission in accordance with the **PYC Complete Technical Specification (Version 1.2)**.

## Milestone 1 Foundation Capabilities

- **Modular Monolith Architecture (`app/Modules` & `app/Support`)**: Domain contracts and strict separation across `Membership`, `Contributions`, `Payments`, `Accounting`, `Expenses`, `Events`, `Notifications`, `Integrations`, and `Reporting`.
- **Security, RBAC & 2FA**:
  - All **7 PYC roles** (`Super Administrator`, `PYC President`, `Treasurer`, `Financial Secretary`, `General Secretary`, `Auditor`, `Member`) and **44 granular permissions** seeded.
  - Public self-registration disabled (invitation-only member onboarding).
  - Mandatory TOTP Two-Factor Authentication (`EnsureAdministratorTwoFactor`) for all executive/administrative roles.
  - Explicit **Maker-Checker Guard** (`MakerCheckerGuard`) preventing any officer (including `Super Administrator`) from approving their own recorded or submitted transactions.
- **Financial & Audit Integrity Primitives**:
  - Immutable `Money` value object storing and formatting integer pesewas (`1000` = `GHS 10.00`).
  - Row-locked transactional `ReferenceGenerator` (`PYC-2026-0001`, `PYC-PAY-2026-00001`, `PYC-RCT-2026-00001`, `PYC-JRN-2026-00001`, `PYC-EXP-2026-00001`, `PYC-EVT-2026-00001`, `PYC-REF-2026-00001`).
  - Append-only `AuditLog` model & `AuditLogger` with automatic secret redaction.
  - Non-secret `SystemSetting` & configurable Parish Youth Council branding (`SystemSettingService`).
- **Executive Portal & Member Portal Shells**: Role-aware dashboards, 2FA enrollment/recovery UI, User & Role management, Branding configuration, and Read-Only Audit Log explorer.

## Local Setup & Commands

```bash
# 1. Run database migrations and seed roles, permissions, and branding defaults
php artisan migrate --seed

# 2. Provision the initial one-time Super Administrator (no shared default password)
php artisan pyc:provision-admin --name="PYC Super Administrator" --email="admin@stceciliapyc.org"

# 3. Build frontend assets
npm run build

# 4. Run automated unit and feature tests
php artisan test

# 5. Start local development server
php artisan serve
```

See [ARCHITECTURE.md](ARCHITECTURE.md) for module boundaries and non-negotiable architectural invariants.
