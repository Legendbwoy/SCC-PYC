<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Membership\Models\Member;
use App\Support\Audit\Models\AuditLog;
use App\Support\Security\PycPermission;
use App\Support\Security\PycRole;
use App\Support\Shared\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $monthlyDues = Money::fromPesewas(
            (int) config('pyc.finance.monthly_dues_pesewas', 1000),
            (string) config('pyc.organization.currency', 'GHS')
        );

        if (! $user->isExecutiveOfficer()) {
            $memberRecord = $user->memberAccount?->member()->with('groups')->first();

            return view('portal.member-dashboard', [
                'user' => $user,
                'member' => $memberRecord,
                'monthlyDues' => $monthlyDues,
            ]);
        }

        $recentAuditLogs = $user->can(PycPermission::AuditLogsView->value)
            ? AuditLog::query()->with('actor')->latest('created_at')->limit(8)->get()
            : collect();

        return view('portal.executive-dashboard', [
            'user' => $user,
            'monthlyDues' => $monthlyDues,
            'stats' => [
                'active_members' => Member::query()->where('status', 'active')->count(),
                'pending_members' => Member::query()->where('status', 'pending')->count(),
                'total_users' => User::query()->count(),
                'active_officers' => User::role(PycRole::executiveRoleNames())->where('status', 'active')->count(),
            ],
            'recentAuditLogs' => $recentAuditLogs,
        ]);
    }
}
