<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\Security\PycPermission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ModuleOverviewController extends Controller
{
    /**
     * @var array<string, array{title: string, sprint: string, permission: string, summary: string, deliverables: list<string>, invariants: list<string>}>
     */
    private const MODULES = [
        'membership' => [
            'title' => 'Membership & Google Forms Sync',
            'sprint' => 'Sprint 2',
            'permission' => PycPermission::MembersView->value,
            'summary' => 'Synchronize Google Forms submissions idempotently, flag duplicate candidates for human review, approve applications, issue PYC-YYYY-NNNN numbers, and send hashed single-use portal invitations.',
            'deliverables' => [
                'GoogleSheetsService & MemberSyncService idempotent synchronization',
                'Phone/email normalization and duplicate candidate review queue',
                'Secretary approval/rejection workflow with full traceability',
                'Automatic PYC-2026-0001 member numbering and time-limited invitations',
            ],
            'invariants' => [
                'Google Forms submissions create pending applications, never active portal accounts.',
                'Membership services never write directly to accounting ledger tables.',
            ],
        ],
        'contributions' => [
            'title' => 'Dues, Contributions & Obligations',
            'sprint' => 'Sprint 4',
            'permission' => PycPermission::DuesView->value,
            'summary' => 'Shared campaign and obligation engine powering confirmed GHS 10.00 (1,000 pesewas) monthly youth dues, event contributions, partial/advance allocations, and audited exemptions.',
            'deliverables' => [
                'Idempotent monthly GHS 10.00 (1,000 pesewas) obligation generator',
                'Shared contribution_campaigns and member_obligations engine',
                'Operational arrears tracking without inflating cash-basis income',
                'Authorized waivers, exemptions, and obligation adjustments',
            ],
            'invariants' => [
                'Unique (member, campaign, period) constraint prevents duplicate monthly dues.',
                'All amounts are stored and calculated strictly in integer pesewas.',
            ],
        ],
        'payments' => [
            'title' => 'Payments, Approvals & Official Receipts',
            'sprint' => 'Sprint 5',
            'permission' => PycPermission::PaymentsView->value,
            'summary' => 'Accept online Paystack payments with HMAC webhook verification and controlled manual cash payments enforcing strict maker-checker approval before allocation, posting, and PDF receipt generation.',
            'deliverables' => [
                'Paystack checkout initialization, server-side reference verification & signed webhook handler',
                'Manual cash payment recording and independent officer approval workflow',
                'Multi-obligation partial and advance payment allocation',
                'Automatic PYC-RCT-YYYY-NNNNN official receipt generation after posting',
            ],
            'invariants' => [
                'The officer recording a cash payment can never approve that same payment.',
                'Receipts and payment notifications fire only after balanced journal posting.',
            ],
        ],
        'accounting' => [
            'title' => 'Double-Entry Accounting & Ledger',
            'sprint' => 'Sprint 6',
            'permission' => PycPermission::AccountingView->value,
            'summary' => 'Immutable double-entry ledger governed solely by AccountingPostingService, with chart of accounts (1000–5400), calendar-year periods, reversal journals, and Paystack settlement reconciliation.',
            'deliverables' => [
                'Chart of accounts (Cash in Hand, PYC Bank, Paystack Clearing, Dues/Event Income, Expenses)',
                'Sole-authority JournalPostingService enforcing debit == credit in pesewas',
                'Calendar-year monthly/annual period closure controls',
                'Traceable reversing journals in place of edits or deletions',
            ],
            'invariants' => [
                'Posted journal entries are immutable; financial records are never deleted.',
                'Closed financial periods block ordinary posting.',
            ],
        ],
        'expenses' => [
            'title' => 'Expense Management & Dual Authorization',
            'sprint' => 'Sprint 6',
            'permission' => PycPermission::ExpensesView->value,
            'summary' => 'Record parish youth council expenses by category, submit for dual authorization by an independent officer, and post disbursements through AccountingPostingService.',
            'deliverables' => [
                'Expense categories mapped to 5000–5400 expense accounts',
                'Draft, submission, independent approval/rejection, and payment lifecycle',
                'Supporting voucher/receipt attachment tracking',
                'Automatic PYC-EXP-YYYY-NNNNN numbering and ledger posting',
            ],
            'invariants' => [
                'Expense submitter cannot approve their own expense under maker-checker rules.',
            ],
        ],
        'events' => [
            'title' => 'Parish Youth Events & Registrations',
            'sprint' => 'Sprint 7',
            'permission' => PycPermission::DashboardView->value,
            'summary' => 'Publish PYC events, manage member registrations and attendance, link optional event contributions to the shared obligation engine, and dispatch reminders.',
            'deliverables' => [
                'PYC-EVT-YYYY-NNNNN event management, publishing, and venue/date scheduling',
                'Member portal registration, cancellation, and attendance tracking',
                'Shared contribution campaign integration (no separate event payment ledger)',
                'Targeted SMS, SMTP email, and in-app event announcements and reminders',
            ],
            'invariants' => [
                'Event contributions reuse the shared obligations, allocations, and accounting engine.',
            ],
        ],
        'notifications' => [
            'title' => 'SMS, Email & In-App Communications',
            'sprint' => 'Milestone N1 / Sprint 7',
            'permission' => PycPermission::NotificationsView->value,
            'summary' => 'Provider-independent notification orchestrator with configurable generic HTTP SMS adapter, SMTP email via Laravel Mail, versioned templates, consent controls, and exponential retry queues.',
            'deliverables' => [
                'GenericHttpSmsGateway & SmtpEmailProvider behind clean contracts',
                '18 versioned templates with strict variable validation and GSM segment estimation',
                'Independent SMS and email delivery tracking across priority queues',
                'Member communication preferences and Ghanaian data-protection consent logs',
            ],
            'invariants' => [
                'Notification failures never roll back or reverse posted financial transactions.',
                'Raw provider API tokens and SMTP secrets live in environment config, never system_settings.',
            ],
        ],
        'reports' => [
            'title' => 'Executive & Auditor Reports',
            'sprint' => 'Sprint 6–7',
            'permission' => PycPermission::ReportsView->value,
            'summary' => 'Read-only membership, arrears, cash/bank book, trial balance, income & expenditure, and communications delivery reports with reconcilable PDF and Excel exports.',
            'deliverables' => [
                'Monthly dues expected vs collected & member arrears statements',
                'Ledger-derived Trial Balance, Income & Expenditure, and Cash/Bank books',
                'Membership register by status/group and Google sync exception reports',
                'Timestamped, user-attributed DomPDF and Excel exports',
            ],
            'invariants' => [
                'All financial reports derive strictly from posted journal lines, never manual totals.',
            ],
        ],
    ];

    public function __invoke(Request $request, string $module): View
    {
        if (! isset(self::MODULES[$module])) {
            throw new NotFoundHttpException;
        }

        $config = self::MODULES[$module];
        abort_unless($request->user()?->can($config['permission']), 403);

        return view('portal.module-overview', [
            'moduleKey' => $module,
            'module' => $config,
        ]);
    }
}
