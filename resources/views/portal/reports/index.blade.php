@component('layouts.app', [
    'title' => 'Reports & Exports',
    'heading' => 'Executive & Auditor Reports & Exports',
    'subheading' => 'Read-only membership, financial ledger, and communications analytics with reconcilable PDF and Excel (CSV) exports.',
])
    <!-- Date Filter & Export Actions -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs flex flex-wrap items-end justify-between gap-4">
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-700">From Date</label>
                <input type="date" name="from" value="{{ $snapshot['from_date'] }}" class="mt-1 rounded-xl border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700">To Date</label>
                <input type="date" name="to" value="{{ $snapshot['to_date'] }}" class="mt-1 rounded-xl border border-slate-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white cursor-pointer">
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
                    <div class="inline-flex rounded-xl border border-slate-200 overflow-hidden bg-slate-50">
                        <span class="px-2.5 py-1.5 font-medium text-slate-700">{{ $label }}:</span>
                        <a href="{{ route('reports.export', ['format' => 'pdf', 'report' => $key, 'from' => $snapshot['from_date'], 'to' => $snapshot['to_date']]) }}"
                           class="px-2 py-1.5 bg-white hover:bg-teal-50 font-semibold text-teal-700 border-l border-slate-200">PDF</a>
                        <a href="{{ route('reports.export', ['format' => 'csv', 'report' => $key, 'from' => $snapshot['from_date'], 'to' => $snapshot['to_date']]) }}"
                           class="px-2 py-1.5 bg-white hover:bg-emerald-50 font-semibold text-emerald-700 border-l border-slate-200">Excel CSV</a>
                    </div>
                @endforeach
            </div>
        @endcan
    </div>

    <!-- Visual Charts (Chart.js) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <h2 class="text-base font-bold text-slate-900 mb-4">Financial Summary (GHS)</h2>
            <canvas id="pycFinanceChart" height="140"></canvas>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <h2 class="text-base font-bold text-slate-900 mb-4">Membership Register by Status</h2>
            <canvas id="pycMembershipChart" height="140"></canvas>
        </div>
    </div>

    <!-- Cash Book & Bank Book Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-base font-bold text-slate-900">Cash Book (1010 — Cash in Hand)</h2>
                <span class="font-mono text-sm font-bold text-teal-800">{{ $snapshot['finance']['cash_book']['net_formatted'] }}</span>
            </div>
            <p class="text-xs text-slate-500">{{ count($snapshot['finance']['cash_book']['lines']) }} posted journal line(s) in period.</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-base font-bold text-slate-900">Bank Book (1020 — PYC Bank Account)</h2>
                <span class="font-mono text-sm font-bold text-teal-800">{{ $snapshot['finance']['bank_book']['net_formatted'] }}</span>
            </div>
            <p class="text-xs text-slate-500">{{ count($snapshot['finance']['bank_book']['lines']) }} posted journal line(s) in period.</p>
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
                            backgroundColor: ['#0f766e', '#059669', '#e11d48', '#d97706']
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
                            backgroundColor: ['#059669', '#d97706', '#e11d48', '#64748b']
                        }]
                    },
                    options: { responsive: true }
                });
            }
        });
    </script>
@endcomponent
