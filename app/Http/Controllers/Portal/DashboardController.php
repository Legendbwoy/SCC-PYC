<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Contributions\Models\MemberObligation;
use App\Modules\Contributions\Services\OutstandingBalanceService;
use App\Modules\Events\Models\EventRegistration;
use App\Modules\Events\Models\PycEvent;
use App\Modules\Membership\Models\Member;
use App\Modules\Membership\Models\MemberGroup;
use App\Modules\Payments\Models\Payment;
use App\Support\Audit\Models\AuditLog;
use App\Support\Security\PycPermission;
use App\Support\Security\PycRole;
use App\Support\Shared\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, OutstandingBalanceService $balanceService): View
    {
        /** @var User $user */
        $user = $request->user();
        $currency = (string) config('pyc.organization.currency', 'GHS');

        $monthlyDues = Money::fromPesewas(
            (int) config('pyc.finance.monthly_dues_pesewas', 1000),
            $currency
        );

        $upcomingEventsQuery = PycEvent::query()
            ->with('contributionCampaign')
            ->where('status', '!=', 'cancelled')
            ->where('starts_at', '>=', now()->subDay())
            ->orderBy('starts_at');

        if (! $user->isExecutiveOfficer()) {
            $memberAccount = $user->memberAccount()->with('member.groups')->first();
            $memberRecord = $memberAccount?->member;
            $statement = $memberRecord !== null ? $balanceService->memberStatement($memberRecord) : null;

            $upcomingEvents = (clone $upcomingEventsQuery)
                ->with([
                    'registrations' => static fn ($q) => $q->where('member_id', $memberRecord?->id ?? 0),
                ])
                ->limit(3)
                ->get();

            $recentPayments = $memberRecord !== null
                ? Payment::query()
                    ->with('receipt')
                    ->where('member_id', $memberRecord->id)
                    ->latest('created_at')
                    ->limit(4)
                    ->get()
                : collect();

            $recentRegistrations = $memberRecord !== null
                ? EventRegistration::query()
                    ->with('event')
                    ->where('member_id', $memberRecord->id)
                    ->latest('created_at')
                    ->limit(3)
                    ->get()
                : collect();

            $unreadNotificationsCount = $user->unreadNotifications()->count();

            return view('portal.member-dashboard', [
                'user' => $user,
                'member' => $memberRecord,
                'statement' => $statement,
                'monthlyDues' => $monthlyDues,
                'nextEvent' => $upcomingEvents->first(),
                'upcomingEvents' => $upcomingEvents,
                'recentPayments' => $recentPayments,
                'recentRegistrations' => $recentRegistrations,
                'unreadNotificationsCount' => $unreadNotificationsCount,
            ]);
        }

        $recentAuditLogs = $user->can(PycPermission::AuditLogsView->value)
            ? AuditLog::query()->with('actor')->latest('created_at')->limit(6)->get()
            : collect();

        $duesTotals = $balanceService->totals();
        $totalPostedPesewas = (int) Payment::query()
            ->where('payment_status', 'posted')
            ->sum('amount_pesewas');

        $currentMonthStart = now()->startOfMonth();
        $thisMonthDuesPesewas = (int) Payment::query()
            ->where('payment_status', 'posted')
            ->where('created_at', '>=', $currentMonthStart)
            ->sum('amount_pesewas');

        $pendingCashCount = Payment::query()
            ->where('approval_status', 'pending_approval')
            ->count();

        $recentApplications = $user->can(PycPermission::MembersView->value)
            ? Member::query()
                ->latest('created_at')
                ->limit(5)
                ->get()
            : collect();

        $recentTransactions = $user->can(PycPermission::PaymentsView->value)
            ? Payment::query()
                ->with(['member', 'receipt'])
                ->where('payment_status', 'posted')
                ->latest('posted_at')
                ->latest('id')
                ->limit(5)
                ->get()
            : collect();

        // Build 6-month Payment vs Dues trend for Chart.js
        $trendLabels = [];
        $trendPayments = [];
        $trendDues = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $periodKey = $monthDate->format('Y-m');
            $trendLabels[] = $monthDate->format('M');

            $monthPayPesewas = (int) Payment::query()
                ->where('payment_status', 'posted')
                ->whereBetween('created_at', [
                    $monthDate->copy()->startOfMonth(),
                    $monthDate->copy()->endOfMonth(),
                ])
                ->sum('amount_pesewas');

            $monthDuesPesewas = (int) MemberObligation::query()
                ->where('period_key', $periodKey)
                ->sum('amount_due_pesewas');

            $trendPayments[] = round($monthPayPesewas / 100, 2);
            $trendDues[] = round($monthDuesPesewas / 100, 2);
        }

        $upcomingEvents = (clone $upcomingEventsQuery)->limit(3)->get();

        return view('portal.executive-dashboard', [
            'user' => $user,
            'monthlyDues' => $monthlyDues,
            'duesTotals' => $duesTotals,
            'totalReceipts' => Money::fromPesewas($totalPostedPesewas, $currency),
            'thisMonthReceipts' => Money::fromPesewas($thisMonthDuesPesewas, $currency),
            'stats' => [
                'active_members' => Member::query()->where('status', 'active')->count(),
                'pending_members' => Member::query()->where('status', 'pending')->count(),
                'total_users' => User::query()->count(),
                'active_officers' => User::role(PycRole::executiveRoleNames())->where('status', 'active')->count(),
                'upcoming_events_count' => (clone $upcomingEventsQuery)->count(),
                'events_this_year' => PycEvent::query()->whereYear('starts_at', now()->year)->count(),
                'active_groups' => MemberGroup::query()->where('is_active', true)->count(),
                'pending_cash_approvals' => $pendingCashCount,
            ],
            'recentApplications' => $recentApplications,
            'recentTransactions' => $recentTransactions,
            'upcomingEvents' => $upcomingEvents,
            'paymentTrend' => [
                'labels' => $trendLabels,
                'payments' => $trendPayments,
                'dues' => $trendDues,
            ],
            'recentAuditLogs' => $recentAuditLogs,
        ]);
    }
}
