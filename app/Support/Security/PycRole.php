<?php

declare(strict_types=1);

namespace App\Support\Security;

enum PycRole: string
{
    case SuperAdministrator = 'Super Administrator';
    case President = 'PYC President';
    case Treasurer = 'Treasurer';
    case FinancialSecretary = 'Financial Secretary';
    case GeneralSecretary = 'General Secretary';
    case Auditor = 'Auditor';
    case Member = 'Member';

    /**
     * All administrative/executive roles that access the Executive Portal.
     *
     * @return list<self>
     */
    public static function executiveRoles(): array
    {
        return [
            self::SuperAdministrator,
            self::President,
            self::Treasurer,
            self::FinancialSecretary,
            self::GeneralSecretary,
            self::Auditor,
        ];
    }

    /**
     * @return list<string>
     */
    public static function executiveRoleNames(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::executiveRoles());
    }

    public function isExecutive(): bool
    {
        return $this !== self::Member;
    }

    public function requiresTwoFactor(): bool
    {
        return $this !== self::Member;
    }

    public function description(): string
    {
        return match ($this) {
            self::SuperAdministrator => 'Technical configuration and user administration; no bypass of financial controls',
            self::President => 'Executive oversight and authorized approvals',
            self::Treasurer => 'Financial oversight and designated payment/expense operations',
            self::FinancialSecretary => 'Payment recording, financial administration and designated verification',
            self::GeneralSecretary => 'Membership administration and event communications',
            self::Auditor => 'Read-only access to financial records and audit trails',
            self::Member => 'Own profile, dues, payments, receipts, event registrations and preferences',
        };
    }

    /**
     * Default permission mapping aligned with Spec Sections 4.1, 4.2, 4.3, and 19.1.
     * Note: Super Administrator receives technical/config permissions, NOT implicit self-approval.
     *
     * @return list<PycPermission>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::SuperAdministrator => [
                PycPermission::DashboardView,
                PycPermission::MembersView,
                PycPermission::GoogleSyncView,
                PycPermission::GoogleSyncRun,
                PycPermission::UsersManage,
                PycPermission::SettingsManage,
                PycPermission::AuditLogsView,
                PycPermission::ReportsView,
                PycPermission::NotificationsView,
                PycPermission::NotificationsManageTemplates,
                PycPermission::NotificationsManagePreferences,
                PycPermission::NotificationsViewDeliveryLogs,
                PycPermission::NotificationsRetryFailed,
                PycPermission::NotificationsManageProviders,
                PycPermission::NotificationsViewCosts,
            ],

            self::President => [
                PycPermission::DashboardView,
                PycPermission::MembersView,
                PycPermission::MembersApprove,
                PycPermission::GoogleSyncView,
                PycPermission::DuesView,
                PycPermission::DuesAdjust,
                PycPermission::DuesExempt,
                PycPermission::PaymentsView,
                PycPermission::PaymentsVerifyCash,
                PycPermission::PaymentsApprove,
                PycPermission::PaymentsRefund,
                PycPermission::PaymentsReverse,
                PycPermission::ExpensesView,
                PycPermission::ExpensesApprove,
                PycPermission::ExpensesReject,
                PycPermission::AccountingView,
                PycPermission::AccountingReverse,
                PycPermission::AccountingClosePeriod,
                PycPermission::ReportsView,
                PycPermission::ReportsExport,
                PycPermission::AuditLogsView,
                PycPermission::NotificationsView,
                PycPermission::NotificationsSend,
                PycPermission::NotificationsSendBulk,
                PycPermission::NotificationsSchedule,
                PycPermission::NotificationsCancel,
                PycPermission::NotificationsViewDeliveryLogs,
                PycPermission::NotificationsViewCosts,
            ],

            self::Treasurer => [
                PycPermission::DashboardView,
                PycPermission::MembersView,
                PycPermission::DuesView,
                PycPermission::DuesGenerate,
                PycPermission::DuesAdjust,
                PycPermission::PaymentsView,
                PycPermission::PaymentsRecordCash,
                PycPermission::PaymentsVerifyCash,
                PycPermission::PaymentsApprove,
                PycPermission::PaymentsAllocate,
                PycPermission::PaymentsRefund,
                PycPermission::ExpensesView,
                PycPermission::ExpensesCreate,
                PycPermission::ExpensesSubmit,
                PycPermission::ExpensesApprove,
                PycPermission::ExpensesReject,
                PycPermission::AccountingView,
                PycPermission::AccountingPost,
                PycPermission::AccountingReverse,
                PycPermission::AccountingClosePeriod,
                PycPermission::ReportsView,
                PycPermission::ReportsExport,
                PycPermission::AuditLogsView,
                PycPermission::NotificationsView,
                PycPermission::NotificationsSend,
            ],

            self::FinancialSecretary => [
                PycPermission::DashboardView,
                PycPermission::MembersView,
                PycPermission::DuesView,
                PycPermission::DuesGenerate,
                PycPermission::PaymentsView,
                PycPermission::PaymentsRecordCash,
                PycPermission::PaymentsVerifyCash,
                PycPermission::PaymentsApprove,
                PycPermission::PaymentsAllocate,
                PycPermission::ExpensesView,
                PycPermission::ExpensesCreate,
                PycPermission::ExpensesSubmit,
                PycPermission::AccountingView,
                PycPermission::AccountingPost,
                PycPermission::ReportsView,
                PycPermission::ReportsExport,
                PycPermission::NotificationsView,
                PycPermission::NotificationsSend,
            ],

            self::GeneralSecretary => [
                PycPermission::DashboardView,
                PycPermission::MembersView,
                PycPermission::MembersCreate,
                PycPermission::MembersUpdate,
                PycPermission::MembersApprove,
                PycPermission::MembersArchive,
                PycPermission::GoogleSyncView,
                PycPermission::GoogleSyncRun,
                PycPermission::ExpensesView,
                PycPermission::ExpensesCreate,
                PycPermission::ExpensesSubmit,
                PycPermission::ReportsView,
                PycPermission::ReportsExport,
                PycPermission::NotificationsView,
                PycPermission::NotificationsSend,
                PycPermission::NotificationsSendBulk,
                PycPermission::NotificationsSchedule,
                PycPermission::NotificationsCancel,
                PycPermission::NotificationsManageTemplates,
                PycPermission::NotificationsViewDeliveryLogs,
            ],

            self::Auditor => [
                PycPermission::DashboardView,
                PycPermission::MembersView,
                PycPermission::GoogleSyncView,
                PycPermission::DuesView,
                PycPermission::PaymentsView,
                PycPermission::ExpensesView,
                PycPermission::AccountingView,
                PycPermission::ReportsView,
                PycPermission::ReportsExport,
                PycPermission::AuditLogsView,
                PycPermission::NotificationsView,
                PycPermission::NotificationsViewDeliveryLogs,
                PycPermission::NotificationsViewCosts,
            ],

            self::Member => [
                PycPermission::DashboardView,
            ],
        };
    }
}
