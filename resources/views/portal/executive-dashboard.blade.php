@php
    $primaryRole = $user->primaryRoleName();
    $isSecretaryView = $primaryRole === 'General Secretary';
    $isFinanceView = in_array($primaryRole, ['Treasurer', 'Financial Secretary'], true);

    $dashboardHeading = match ($primaryRole) {
        'General Secretary' => 'Secretary Dashboard',
        'Treasurer' => 'Treasurer Dashboard',
        'Financial Secretary' => 'Financial Secretary Dashboard',
        'PYC President' => 'President Dashboard',
        'Auditor' => 'Auditor Dashboard',
        default => 'Executive Dashboard',
    };

    $dashboardFocus = match ($primaryRole) {
        'General Secretary' => 'Manage membership and communications',
        'Treasurer', 'Financial Secretary' => 'Financial Overview & Maker-Checker Controls',
        'PYC President' => 'Council Overview & Approvals',
        'Auditor' => 'Read-Only Ledger & Governance Oversight',
        default => 'Council Administration & Governance',
    };
@endphp

@component('layouts.app', [
    'title' => $dashboardHeading,
    'heading' => $dashboardHeading,
    'subheading' => "{$dashboardFocus} — Executive & Administrative Overview",
])
    <!-- Role-Tailored Pastel KPI Cards (Matching Secretary / Treasurer / President Reference Mockups) -->
    @if($isFinanceView)
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="pyc-kpi-green">
                <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3">
                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75"/></svg>
                </div>
                <p class="text-xs font-medium text-slate-500">Total Receipts</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $totalReceipts->format() }}</p>
                <p class="mt-1 text-xs font-semibold text-emerald-700">&uarr; Posted Cash &amp; Paystack</p>
            </div>

            <div class="pyc-kpi-blue">
                <div class="h-10 w-10 rounded-xl bg-sky-100 text-blue-600 flex items-center justify-center mb-3">
                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25"/></svg>
                </div>
                <p class="text-xs font-medium text-slate-500">Monthly Dues Collected</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $duesTotals['collected']->format() }}</p>
                <p class="mt-1 text-xs font-semibold text-blue-700">Rate: {{ $monthlyDues->format() }} / month</p>
            </div>

            <div class="pyc-kpi-purple">
                <div class="h-10 w-10 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center mb-3">
                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>
                <p class="text-xs font-medium text-slate-500">Outstanding Dues</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $duesTotals['outstanding']->format() }}</p>
                <p class="mt-1 text-xs font-semibold text-rose-600">Operational receivables</p>
            </div>

            <div class="pyc-kpi-amber">
                <div class="h-10 w-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center mb-3">
                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622"/></svg>
                </div>
                <p class="text-xs font-medium text-slate-500">Awaiting Cash Approval</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format($stats['pending_cash_approvals']) }}</p>
                <p class="mt-1 text-xs font-semibold text-amber-700">Maker-checker queue</p>
            </div>
        </div>
    @elseif($isSecretaryView)
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="pyc-kpi-blue">
                <div class="h-10 w-10 rounded-xl bg-sky-100 text-blue-600 flex items-center justify-center mb-3">
                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                </div>
                <p class="text-xs font-medium text-slate-500">Total Members</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format($stats['active_members']) }}</p>
                <p class="mt-1 text-xs font-semibold text-blue-700">Approved PYC register</p>
            </div>

            <div class="pyc-kpi-green">
                <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3">
                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>
                <p class="text-xs font-medium text-slate-500">Pending Applications</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format($stats['pending_members']) }}</p>
                <p class="mt-1 text-xs font-semibold text-emerald-700">Awaiting Secretary review</p>
            </div>

            <div class="pyc-kpi-purple">
                <div class="h-10 w-10 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center mb-3">
                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25"/></svg>
                </div>
                <p class="text-xs font-medium text-slate-500">Upcoming Events</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format($stats['upcoming_events_count']) }}</p>
                <p class="mt-1 text-xs font-semibold text-violet-700">Open on parish calendar</p>
            </div>

            <div class="pyc-kpi-amber">
                <div class="h-10 w-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center mb-3">
                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375"/></svg>
                </div>
                <p class="text-xs font-medium text-slate-500">This Month's Dues</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $thisMonthReceipts->format() }}</p>
                <p class="mt-1 text-xs font-semibold text-amber-700">Rate: {{ $monthlyDues->format() }}</p>
            </div>
        </div>
    @else
        <!-- President / Super Administrator / Auditor KPI Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="pyc-kpi-blue">
                <div class="h-10 w-10 rounded-xl bg-sky-100 text-blue-600 flex items-center justify-center mb-3">
                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                </div>
                <p class="text-xs font-medium text-slate-500">Total Members</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format($stats['active_members']) }}</p>
                <p class="mt-1 text-xs font-semibold text-blue-700">{{ number_format($stats['pending_members']) }} pending applications</p>
            </div>

            <div class="pyc-kpi-green">
                <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3">
                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25"/></svg>
                </div>
                <p class="text-xs font-medium text-slate-500">Events This Year</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format($stats['events_this_year']) }}</p>
                <p class="mt-1 text-xs font-semibold text-emerald-700">{{ number_format($stats['upcoming_events_count']) }} upcoming</p>
            </div>

            <div class="pyc-kpi-purple">
                <div class="h-10 w-10 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center mb-3">
                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75Z"/></svg>
                </div>
                <p class="text-xs font-medium text-slate-500">Total Income</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $totalReceipts->format() }}</p>
                <p class="mt-1 text-xs font-semibold text-violet-700">Cash-basis posted receipts</p>
            </div>

            <div class="pyc-kpi-amber">
                <div class="h-10 w-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center mb-3">
                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5"/></svg>
                </div>
                <p class="text-xs font-medium text-slate-500">Active Youth Groups</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format($stats['active_groups']) }}</p>
                <p class="mt-1 text-xs font-semibold text-amber-700">{{ number_format($stats['active_officers']) }} active officers</p>
            </div>
        </div>
    @endif

    <!-- Primary Workspace Row: Council Highlights / Applications + Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        @if($isSecretaryView && $user->can('members.view'))
            <!-- Membership Applications Card (Secretary Mockup) -->
            <div class="lg:col-span-7 pyc-card p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-extrabold text-slate-900">Membership Applications</h2>
                    <a href="{{ route('membership.index') }}" class="text-xs font-bold text-[#1E549F] hover:underline">
                        View All &rarr;
                    </a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($recentApplications as $appMember)
                        <a href="{{ route('membership.show', $appMember) }}" class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50/80 rounded-xl px-2 -mx-2 transition">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="h-9 w-9 rounded-full bg-sky-100 text-[#0F2D59] font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($appMember->first_name, 0, 1) . substr($appMember->last_name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-900 truncate">{{ $appMember->fullName() }}</p>
                                    <p class="text-xs text-slate-500 font-mono">{{ $appMember->member_number ?? ($appMember->phone ?? 'Pending Verification') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="hidden sm:inline text-xs text-slate-400">{{ $appMember->created_at?->format('M j, Y') }}</span>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold capitalize {{ $appMember->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($appMember->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600') }}">
                                    {{ $appMember->status === 'active' ? 'Approved' : $appMember->status }}
                                </span>
                            </div>
                        </a>
                    @empty
                        <p class="py-8 text-center text-xs text-slate-400">No membership applications recorded yet.</p>
                    @endforelse
                </div>
            </div>
        @else
            <!-- Council Highlights & Controls Card (President / Executive Mockup) -->
            <div class="lg:col-span-7 pyc-card p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-extrabold text-slate-900">Council Highlights</h2>
                    <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold">
                        {{ ucfirst((string) config('pyc.finance.accounting_basis')) }} Basis &bull; Maker-Checker Active
                    </span>
                </div>

                <div class="divide-y divide-slate-100 text-sm">
                    <div class="py-3 flex items-center gap-3">
                        <span class="h-6 w-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold shrink-0">&check;</span>
                        <span class="text-slate-700">
                            <strong class="text-slate-900">{{ number_format($stats['active_members']) }}</strong> approved youth members active on the parish register
                            @if($stats['pending_members'] > 0)
                                (<a href="{{ route('membership.index', ['status' => 'pending']) }}" class="font-semibold text-amber-700 underline">{{ $stats['pending_members'] }} awaiting review</a>)
                            @endif
                        </span>
                    </div>
                    <div class="py-3 flex items-center gap-3">
                        <span class="h-6 w-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold shrink-0">&check;</span>
                        <span class="text-slate-700">
                            Standard monthly dues at <strong class="text-slate-900">{{ $monthlyDues->format() }}</strong> &bull; Collected: <strong class="text-emerald-700">{{ $duesTotals['collected']->format() }}</strong> &bull; Arrears: <strong class="text-amber-700">{{ $duesTotals['outstanding']->format() }}</strong>
                        </span>
                    </div>
                    <div class="py-3 flex items-center gap-3">
                        <span class="h-6 w-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold shrink-0">&check;</span>
                        <span class="text-slate-700">
                            @if($upcomingEvents->isNotEmpty())
                                Next event: <strong class="text-slate-900">{{ $upcomingEvents->first()->title }}</strong> ({{ $upcomingEvents->first()->starts_at->timezone('Africa/Accra')->format('M j, Y') }})
                            @else
                                Parish youth events calendar ready for upcoming programs
                            @endif
                        </span>
                    </div>
                    <div class="py-3 flex items-center gap-3">
                        <span class="h-6 w-6 rounded-full bg-sky-100 text-[#0F2D59] flex items-center justify-center text-xs font-bold shrink-0">&check;</span>
                        <span class="text-slate-700">
                            Dual authorization enforced: no officer can self-approve cash collections or expense vouchers
                        </span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Quick Actions Stack (Matching Secretary & President Reference Mockups) -->
        <div class="lg:col-span-5 pyc-card p-6 flex flex-col justify-between space-y-4">
            <div class="space-y-3">
                <h2 class="text-lg font-extrabold text-slate-900">Quick Actions</h2>

                <div class="space-y-2.5 pt-1">
                    @can('notifications.send_bulk')
                        <a href="{{ route('communications.campaigns') }}" class="btn-pyc-primary w-full justify-start px-4 py-3">
                            <svg class="h-4.5 w-4.5 text-sky-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                            <span>Broadcast Announcement</span>
                        </a>
                    @endcan

                    @can('payments.view')
                        <a href="{{ route('payments.index') }}" class="{{ $user->can('notifications.send_bulk') ? 'btn-pyc-outline' : 'btn-pyc-primary' }} w-full justify-start px-4 py-3">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            <span>{{ $stats['pending_cash_approvals'] > 0 ? "Approve Payments ({$stats['pending_cash_approvals']})" : 'Payments & Receipts' }}</span>
                        </a>
                    @endcan

                    @can('members.view')
                        <a href="{{ route('membership.index') }}" class="btn-pyc-outline w-full justify-start px-4 py-3">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z"/></svg>
                            <span>Manage Members</span>
                        </a>
                    @endcan

                    @can('reports.view')
                        <a href="{{ route('reports.index') }}" class="btn-pyc-outline w-full justify-start px-4 py-3">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75"/></svg>
                            <span>View Full Reports</span>
                        </a>
                    @endcan
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>2FA Status: <strong class="{{ $user->hasConfirmedTwoFactorAuthentication() ? 'text-emerald-700' : 'text-amber-700' }}">{{ $user->hasConfirmedTwoFactorAuthentication() ? 'Confirmed' : 'Pending' }}</strong></span>
                <a href="{{ route('profile.security') }}" class="font-bold text-[#1E549F] hover:underline">Security Settings &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Financial Trend Chart & Recent Transactions Row (Matching Treasurer Dashboard Mockup) -->
    @if($user->can('payments.view') || $user->can('reports.view'))
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-7 pyc-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-900">Payment Trend (Last 6 Months)</h2>
                        <p class="text-xs text-slate-500">Posted Receipts vs Monthly Dues Billed (GHS)</p>
                    </div>
                </div>
                <canvas id="pycExecutiveTrendChart" height="130"></canvas>
            </div>

            <div class="lg:col-span-5 pyc-card p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-extrabold text-slate-900">Recent Transactions</h2>
                    @can('payments.view')
                        <a href="{{ route('payments.index') }}" class="text-xs font-bold text-[#1E549F] hover:underline">View All &rarr;</a>
                    @endcan
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($recentTransactions as $tx)
                        <div class="py-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="h-8 w-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-xs shrink-0">&check;</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-900 capitalize">{{ $tx->method }} Payment</p>
                                    <p class="text-xs text-slate-500 truncate">
                                        {{ $tx->member?->fullName() ?? $tx->reference }} &bull; {{ ($tx->posted_at ?? $tx->created_at)?->timezone('Africa/Accra')->format('M j, Y') }}
                                    </p>
                                </div>
                            </div>
                            <span class="font-mono text-xs font-bold text-slate-900 shrink-0">{{ $tx->amountMoney()->format() }}</span>
                        </div>
                    @empty
                        <p class="py-10 text-center text-xs text-slate-400">No posted payment transactions yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('pycExecutiveTrendChart');
                if (!ctx || !window.Chart) return;

                new window.Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: @json($paymentTrend['labels']),
                        datasets: [
                            {
                                label: 'Payments',
                                data: @json($paymentTrend['payments']),
                                backgroundColor: '#16407B',
                                borderRadius: 6,
                            },
                            {
                                label: 'Dues Billed',
                                data: @json($paymentTrend['dues']),
                                backgroundColor: '#22C55E',
                                borderRadius: 6,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            });
        </script>
    @endif

    <!-- Recent Append-Only Audit Trail -->
    @can('audit_logs.view')
        <div class="pyc-card overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Recent Append-Only Audit Activity</h2>
                    <p class="text-xs text-slate-500">Immutable security and administrative event log</p>
                </div>
                <a href="{{ route('admin.audit-logs.index') }}" class="text-xs font-bold text-[#1E549F] hover:underline">
                    View full audit trail &rarr;
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200/80 text-sm">
                    <thead class="bg-slate-50/80 text-xs uppercase text-slate-500">
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
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-6 py-3 whitespace-nowrap text-xs text-slate-500">
                                    {{ $log->created_at?->timezone('Africa/Accra')->format('d M Y H:i:s') }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-[#0F2D59]">
                                        {{ $log->category }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap font-mono text-xs font-semibold text-[#1E549F]">{{ $log->event }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs font-medium text-slate-700">{{ $log->actor?->name ?? 'System' }}</td>
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
