<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit\Models\AuditLog;
use App\Support\Security\PycPermission;
use App\Support\Security\PycRole;
use App\Support\Shared\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
            return view('portal.member-dashboard', [
                'user' => $user,
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
                'total_users' => User::query()->count(),
                'active_officers' => User::role(PycRole::executiveRoleNames())->where('status', 'active')->count(),
                'roles_configured' => Role::query()->count(),
                'permissions_configured' => Permission::query()->count(),
            ],
            'recentAuditLogs' => $recentAuditLogs,
        ]);
    }
}
