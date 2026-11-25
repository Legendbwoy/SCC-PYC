# St. Cecilia Catholic Church — Parish Youth Council (SCC-PYC)
## Modular Monolith Architecture & Invariants

This application implements the **PYC Complete Technical Specification (Version 1.2)** as a Laravel 12 modular monolith with MySQL/MariaDB, Blade, Livewire, Tailwind CSS, Laravel Fortify (with TOTP 2FA), and Spatie Laravel Permission.

### Module Layout (`app/`)

```text
app/
  Modules/
    Membership/       # Member register, Google Sheets sync, duplicate review, invitations
    Contributions/    # Contribution types/campaigns, GHS 10.00 monthly dues, obligations
    Payments/         # Paystack online checkout/webhooks, cash recording, allocations, receipts
    Accounting/       # Sole journal posting authority, chart of accounts, periods, reversals
    Expenses/         # Expense submission, maker-checker approval, disbursement
    Events/           # Parish youth events, announcements, registrations, attendance
    Notifications/    # Generic HTTP SMS adapter, SMTP email, in-app alerts, templates, consent
    Integrations/     # Isolated external provider clients, sync logs, webhook verification
    Reporting/        # Read-only ledger-derived financial, membership, and delivery reports
  Support/
    Audit/            # Append-only immutable AuditLog model and AuditLogger service
    Security/         # Role/Permission enums, MakerCheckerGuard, 2FA enforcement middleware
    Shared/           # Integer-pesewas Money VO, transactional ReferenceGenerator, SystemSettings
```

### Non-Negotiable Architectural Invariants (Spec Section 3.5)

1. **Ledger Isolation:** Membership, Contributions, Payments, Events, and Expenses modules **never** write directly to `journal_entries` or `journal_lines`.
2. **Sole Posting Authority:** `App\Modules\Accounting\Contracts\AccountingPostingServiceInterface` is the sole authority for creating and posting balanced double-entry journals.
3. **Obligation Allocation:** Every member payment must allocate against `member_obligations` before journal posting and receipt generation.
4. **Thin Controllers:** Controllers coordinate HTTP requests/responses only; validation lives in Form Requests, authorization in Policies, and workflows in Services/Actions.
5. **Read-Only Reports:** Reports derive strictly from authoritative posted records, never manually maintained counters.
6. **Member Data Isolation:** Users with the `Member` role can view and act only upon their own profile, obligations, payments, receipts, and event registrations.
7. **Maker-Checker Enforcement:** No user (including `Super Administrator`) may approve a cash payment, expense, or adjustment that they recorded or submitted themselves (`MakerCheckerGuard`).
8. **Immutable Journals & Audit Logs:** Posted financial records and `audit_logs` cannot be edited or physically deleted; financial corrections require authorized reversing journals.
9. **Integer Pesewas:** All monetary values are stored and calculated in integer pesewas (`1000` = `GHS 10.00`), never floating-point numbers (`App\Support\Shared\Money`).
10. **Transactional Sequences:** Official references (`PYC-2026-0001`, `PYC-PAY-2026-00001`, etc.) are generated inside row-locked database transactions (`App\Support\Shared\ReferenceGenerator`).
11. **Idempotent Webhooks & Notifications:** Provider webhooks and notification dispatches deduplicate on unique event/idempotency keys, and notification failures never roll back posted financial transactions.
