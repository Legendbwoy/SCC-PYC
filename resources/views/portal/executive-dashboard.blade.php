@component('layouts.app', [
    'title' => 'Executive Dashboard',
    'heading' => 'Executive & Administrative Overview',
    'subheading' => ($pycBranding['organization_name'] ?? 'St. Cecilia Catholic Church — Parish Youth Council') . ' — Milestone 1 Foundation Active',
])
    <!-- Top KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Monthly Youth Dues</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $monthlyDues->format() }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ number_format($monthlyDues->pesewas) }} pesewas / member / month</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Portal Accounts</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['total_users']) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ number_format($stats['active_officers']) }} active council officers</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">RBAC Security Matrix</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $stats['roles_configured'] }} Roles</p>
            <p class="mt-1 text-xs text-slate-500">{{ $stats['permissions_configured'] }} granular permissions active</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Accounting &amp; Controls</p>
            <p class="mt-2 text-2xl font-bold text-teal-700 capitalize">{{ config('pyc.finance.accounting_basis') }} Basis</p>
            <p class="mt-1 text-xs text-slate-500">Maker-checker &amp; immutable ledger</p>
        </div>
    </div>

    <!-- Active Architectural Safeguards -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-bold text-slate-900">Confirmed PYC System Controls (Spec v1.2)</h2>
                <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">Enforced</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="font-semibold text-slate-900">Dual Authorization (Maker-Checker)</p>
                    <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                        No officer (including Super Administrator) can approve a cash payment or expense they recorded or submitted themselves.
                    </p>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="font-semibold text-slate-900">Sole Journal Posting Authority</p>
                    <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                        Domain modules are isolated from ledger tables; all balanced postings route through <code class="text-teal-800">AccountingPostingService</code>.
                    </p>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="font-semibold text-slate-900">Integer Pesewas Precision</p>
                    <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                        All monetary values use integer pesewas (<code class="text-teal-800">1000</code> = <code class="text-teal-800">GHS 10.00</code>) with transactional <code class="text-teal-800">PYC-*</code> sequence locks.
                    </p>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="font-semibold text-slate-900">Invitation-Only Member Onboarding</p>
                    <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                        Public self-signup is disabled. Google Form submissions await Secretary review before issuing <code class="text-teal-800">PYC-YYYY-NNNN</code> invitations.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs flex flex-col justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Your Session Security</h2>
                <p class="mt-1 text-xs text-slate-500">Signed in as <strong class="text-slate-700">{{ $user->primaryRoleName() }}</strong></p>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <dt class="text-slate-500">Two-Factor Auth</dt>
                        <dd class="font-semibold {{ $user->hasConfirmedTwoFactorAuthentication() ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $user->hasConfirmedTwoFactorAuthentication() ? 'Confirmed (TOTP)' : 'Setup Pending' }}
                        </dd>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <dt class="text-slate-500">Financial Year</dt>
                        <dd class="font-medium text-slate-800">1 Jan – 31 Dec {{ now()->year }}</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-slate-500">Notification Drivers</dt>
                        <dd class="font-medium text-slate-800">HTTP SMS + SMTP</dd>
                    </div>
                </dl>
            </div>

            <a href="{{ route('profile.security') }}"
               class="mt-5 inline-flex items-center justify-center w-full rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white hover:bg-slate-800 transition">
                Manage 2FA &amp; Security Settings
            </a>
        </div>
    </div>

    <!-- Recent Append-Only Audit Trail -->
    @can('audit_logs.view')
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Recent Append-Only Audit Activity</h2>
                    <p class="text-xs text-slate-500">Immutable security and administrative event log</p>
                </div>
                <a href="{{ route('admin.audit-logs.index') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800">
                    View full audit trail &rarr;
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold">Timestamp (Africa/Accra)</th>
                            <th class="px-6 py-3 text-left font-semibold">Category</th>
                            <th class="px-6 py-3 text-left font-semibold">Event</th>
                            <th class="px-6 py-3 text-left font-semibold">Actor</th>
                            <th class="px-6 py-3 text-left font-semibold">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentAuditLogs as $log)
                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-xs text-slate-500">
                                    {{ $log->created_at?->timezone('Africa/Accra')->format('d M Y H:i:s') }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ $log->category }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap font-mono text-xs text-teal-800">{{ $log->event }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs text-slate-700">{{ $log->actor?->name ?? 'System' }}</td>
                                <td class="px-6 py-3 text-xs text-slate-600">{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-slate-400">No audit entries recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endcan
@endcomponent
