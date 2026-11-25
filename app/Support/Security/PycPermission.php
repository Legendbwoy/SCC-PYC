<?php

declare(strict_types=1);

namespace App\Support\Security;

/**
 * Complete 43-item permission catalogue from Specification Section 4.2.
 */
enum PycPermission: string
{
    case DashboardView = 'dashboard.view';

    // Membership & Google Sync
    case MembersView = 'members.view';
    case MembersCreate = 'members.create';
    case MembersUpdate = 'members.update';
    case MembersApprove = 'members.approve';
    case MembersArchive = 'members.archive';
    case GoogleSyncView = 'google_sync.view';
    case GoogleSyncRun = 'google_sync.run';

    // Dues & Obligations
    case DuesView = 'dues.view';
    case DuesGenerate = 'dues.generate';
    case DuesAdjust = 'dues.adjust';
    case DuesExempt = 'dues.exempt';

    // Payments
    case PaymentsView = 'payments.view';
    case PaymentsRecordCash = 'payments.record_cash';
    case PaymentsVerifyCash = 'payments.verify_cash';
    case PaymentsApprove = 'payments.approve';
    case PaymentsAllocate = 'payments.allocate';
    case PaymentsRefund = 'payments.refund';
    case PaymentsReverse = 'payments.reverse';

    // Expenses
    case ExpensesView = 'expenses.view';
    case ExpensesCreate = 'expenses.create';
    case ExpensesSubmit = 'expenses.submit';
    case ExpensesApprove = 'expenses.approve';
    case ExpensesReject = 'expenses.reject';

    // Accounting
    case AccountingView = 'accounting.view';
    case AccountingPost = 'accounting.post';
    case AccountingReverse = 'accounting.reverse';
    case AccountingClosePeriod = 'accounting.close_period';

    // Reports, Audit, Users & Settings
    case ReportsView = 'reports.view';
    case ReportsExport = 'reports.export';
    case AuditLogsView = 'audit_logs.view';
    case UsersManage = 'users.manage';
    case SettingsManage = 'settings.manage';

    // Notifications
    case NotificationsView = 'notifications.view';
    case NotificationsSend = 'notifications.send';
    case NotificationsSendBulk = 'notifications.send_bulk';
    case NotificationsSchedule = 'notifications.schedule';
    case NotificationsCancel = 'notifications.cancel';
    case NotificationsManageTemplates = 'notifications.manage_templates';
    case NotificationsManagePreferences = 'notifications.manage_preferences';
    case NotificationsViewDeliveryLogs = 'notifications.view_delivery_logs';
    case NotificationsRetryFailed = 'notifications.retry_failed';
    case NotificationsManageProviders = 'notifications.manage_providers';
    case NotificationsViewCosts = 'notifications.view_costs';

    public function domain(): string
    {
        return explode('.', $this->value)[0];
    }
}
