# Deployment, Operations & Production Launch Guide (`SCC-PYC`)

This guide covers **Section 15 (Deployment and operations)** and **Section 19 (Outstanding decisions and production prerequisites)** of the PYC Complete Technical Specification (Version 1.2).

---

## 1. Hosting & Environment Separation

| Environment | Purpose | Configuration Rules |
| :--- | :--- | :--- |
| **Local** | Feature development & automated tests | `APP_ENV=local`, XAMPP MariaDB (`scc_pyc` / `scc_pyc_test`) |
| **Staging** | UAT, Paystack test mode, Google test sheet, SMS/SMTP sandbox | `APP_ENV=staging`, `APP_DEBUG=false`, isolated test credentials |
| **Production** | Live parish member register & live GHS financial records | `APP_ENV=production`, `APP_DEBUG=false`, HTTPS forced, encrypted backups |

## 2. Server Provisioning (Ubuntu LTS)

1. Copy [deploy/nginx/scc-pyc.conf](deploy/nginx/scc-pyc.conf) to `/etc/nginx/sites-available/scc-pyc.conf` and provision Let's Encrypt TLS certificates.
2. Copy [deploy/supervisor/scc-pyc-workers.conf](deploy/supervisor/scc-pyc-workers.conf) to `/etc/supervisor/conf.d/` and run `supervisorctl reread && supervisorctl update`.
   - Separate worker pools isolate `high_priority` (receipts & 2FA/account invitations), `notifications`/`delivery_webhooks`, and `bulk_notifications`.
3. Install [deploy/cron/scc-pyc-scheduler](deploy/cron/scc-pyc-scheduler) into `/etc/cron.d/scc-pyc-scheduler`.
4. Provision the one-time initial `Super Administrator` (no default password is ever seeded):
   ```bash
   php artisan pyc:provision-admin --name="PYC Super Administrator" --email="admin@stceciliapyc.org"
   ```
   Sign in immediately and enroll TOTP Two-Factor Authentication.

## 3. Backup, Recovery & RPO / RTO Targets

- **Daily Automated Encrypted Backups**: [deploy/scripts/backup.sh](deploy/scripts/backup.sh) dumps MySQL (`--single-transaction`) + `storage/app` and encrypts with `AES-256-CBC` (`PBKDF2`, 100k iterations). Sync `/var/backups/scc-pyc` to off-server object storage.
- **Target RPO**: 24 hours (daily off-site encrypted snapshot; 1 hour if binary log shipping is enabled).
- **Target RTO**: < 2 hours using [deploy/scripts/restore-drill.sh](deploy/scripts/restore-drill.sh).
- **Quarterly Restore Drills**: Run `deploy/scripts/restore-drill.sh <archive.enc> scc_pyc_restore_drill` in staging every quarter.

## 4. Operational Monitoring

Run via CLI or cron alerting:
```bash
php artisan pyc:health-check
```
Or query `GET /api/v1/system/health` as an authenticated `Super Administrator`, `PYC President`, `Treasurer`, or `Auditor`.

## 5. Production Launch Checklist (Spec Section 19.2)

- [ ] Confirm official St. Cecilia Catholic Church PYC branding, logo, and contact details in `/admin/settings`.
- [ ] Confirm cash-basis accounting policy and record any opening fund balances (`3000 Accumulated Funds`).
- [ ] Provision council officers (`PYC President`, `Treasurer`, `Financial Secretary`, `General Secretary`, `Auditor`) and verify each officer completes TOTP 2FA setup.
- [ ] Set `GOOGLE_SHEETS_SPREADSHEET_ID` and `GOOGLE_SERVICE_ACCOUNT_JSON_PATH` in `.env` and run a manual sync test in `/membership/google-sync`.
- [ ] Set live `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY`, and `PAYSTACK_WEBHOOK_SECRET`, and configure Paystack webhook URL `https://<domain>/api/v1/webhooks/paystack`.
- [ ] Select production Ghana SMS gateway, configure `SMS_ENDPOINT`, `SMS_API_TOKEN`, and approved `SMS_SENDER_ID`, and dispatch an authorized test SMS in `/communications`.
- [ ] Configure verified `MAIL_*` SMTP credentials and dispatch an authorized test email in `/communications`.
- [ ] Review Ghanaian data protection consent wording on `/invitations/{token}` and `/profile/notifications`.
- [ ] Execute one end-to-end backup & restore drill before go-live.
