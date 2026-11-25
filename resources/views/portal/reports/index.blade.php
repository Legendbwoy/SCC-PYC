@component('layouts.app', [
    'title' => 'Reports & Exports',
    'heading' => 'Executive & Auditor Reports',
    'subheading' => 'Read-only membership, financial ledger, and communications analytics with reconcilable PDF and Excel (CSV) exports.',
])
    <!-- Date Filter & Export Actions -->
    <div class="pyc-card p-5 flex flex-wrap items-end justify-between gap-4">
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">From Date</label>
                <input type="date" name="from" value="{{ $snapshot['from_date'] }}" class="pyc-input py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">To Date</label>
                <input type="date" name="to" value="{{ $snapshot['to_date'] }}" class="pyc-input py-2">
            </div>
            <button type="submit" class="btn-pyc-primary py-2 px-4 text-xs">
                Apply Period
            </button>
        </form>

        @can('reports.export')
            <div class="flex flex-wrap gap-2 text-xs">
                @foreach([
                    'trial_balance' => 'Trial Balance',
                    'income_expenditure' => 'Income & Expenditure',
                    'member_arrears' => 'Member Arrears',
                    'membership_register' => 'Membership Register',
                    'expenses_register' => 'Expenses Register',
                ] as $key => $label)
                    <div class="inline-flex rounded-xl border border-slate-200 overflow-hidden bg-slate-50/80">
                        <span class="px-2.5 py-1.5 font-semibold text-slate-700">{{ $label }}:</span>
                        <a href="{{ route('reports.export', ['format' => 'pdf', 'report' => $key, 'from' => $snapshot['from_date'], 'to' => $snapshot['to_date']]) }}"
                           class="px-2.5 py-1.5 bg-white hover:bg-sky-50 font-bold text-[#0F2D59] border-l border-slate-200">PDF</a>
                        <a href="{{ route('reports.export', ['format' => 'csv', 'report' => $key, 'from' => $snapshot['from_date'], 'to' => $snapshot['to_date']]) }}"
                           class="px-2.5 py-1.5 bg-white hover:bg-emerald-50 font-bold text-emerald-700 border-l border-slate-200">Excel CSV</a>
                    </div>
                @endforeach
            </div>
        @endcan
    </div>

    <!-- Visual Charts (Chart.js) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="pyc-card p-6">
            <h2 class="text-base font-extrabold text-slate-900 mb-4">Financial Summary (GHS)</h2>
            <canvas id="pycFinanceChart" height="140"></canvas>
        </div>

        <div class="pyc-card p-6">
            <h2 class="text-base font-extrabold text-slate-900 mb-4">Membership Register by Status</h2>
            <canvas id="pycMembershipChart" height="140"></canvas>
        </div>
    </div>

    <!-- Cash Book & Bank Book Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="pyc-kpi-blue">
            <div class="flex justify-between items-center mb-2">
                <h2 class="text-base font-extrabold text-slate-900">Cash Book (1010 — Cash in Hand)</h2>
                <span class="font-mono text-sm font-extrabold text-[#0F2D59]">{{ $snapshot['finance']['cash_book']['net_formatted'] }}</span>
            </div>
            <p class="text-xs text-slate-600">{{ count($snapshot['finance']['cash_book']['lines']) }} posted journal line(s) in period.</p>
        </div>

        <div class="pyc-kpi-green">
            <div class="flex justify-between items-center mb-2">
                <h2 class="text-base font-extrabold text-slate-900">Bank Book (1020 — PYC Bank Account)</h2>
                <span class="font-mono text-sm font-extrabold text-emerald-800">{{ $snapshot['finance']['bank_book']['net_formatted'] }}</span>
            </div>
            <p class="text-xs text-slate-600">{{ count($snapshot['finance']['bank_book']['lines']) }} posted journal line(s) in period.</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.Chart) return;

            const finCtx = document.getElementById('pycFinanceChart');
            if (finCtx) {
                new window.Chart(finCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Dues Expected', 'Recognized Income', 'Posted Expenses', 'Operational Arrears'],
                        datasets: [{
                            label: 'Amount (GHS)',
                            data: [
                                {{ round($snapshot['finance']['dues_totals']['expected']->pesewas / 100, 2) }},
                                {{ round($snapshot['finance']['income_expenditure']['total_income']['amount_pesewas'] / 100, 2) }},
                                {{ round($snapshot['finance']['income_expenditure']['total_expenses']['amount_pesewas'] / 100, 2) }},
                                {{ round($snapshot['finance']['dues_totals']['outstanding']->pesewas / 100, 2) }}
                            ],
                            backgroundColor: ['#16407B', '#22C55E', '#E11D48', '#F59E0B'],
                            borderRadius: 6
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { display: false } } }
                });
            }

            const memCtx = document.getElementById('pycMembershipChart');
            if (memCtx) {
                new window.Chart(memCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active', 'Pending', 'Rejected', 'Archived'],
                        datasets: [{
                            data: [
                                {{ (int) ($snapshot['membership']['by_status']['active'] ?? 0) }},
                                {{ (int) ($snapshot['membership']['by_status']['pending'] ?? 0) }},
                                {{ (int) ($snapshot['membership']['by_status']['rejected'] ?? 0) }},
                                {{ (int) ($snapshot['membership']['by_status']['archived'] ?? 0) }}
                            ],
                            backgroundColor: ['#22C55E', '#F59E0B', '#E11D48', '#64748B']
                        }]
                    },
                    options: { responsive: true }
                });
            }
        });
    </script>
@endcomponent
