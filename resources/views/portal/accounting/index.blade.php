@component('layouts.app', [
    'title' => 'Accounting Ledger',
    'heading' => 'Double-Entry Accounting Ledger, Trial Balance & Periods',
    'subheading' => 'Immutable integer-pesewas journals governed solely by AccountingPostingService.',
])
    <!-- Ledger Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Trial Balance Debits</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $trialBalance['total_debit_formatted'] }}</p>
            <p class="mt-1 text-xs text-emerald-700 font-semibold">
                {{ $trialBalance['is_balanced'] ? 'Balanced (Debits == Credits)' : 'Imbalance Detected' }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">YTD Recognized Income</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700">GHS {{ $incomeStatement['total_income']['formatted_amount'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Cash-basis posted income</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">YTD Posted Expenses</p>
            <p class="mt-2 text-2xl font-bold text-rose-700">GHS {{ $incomeStatement['total_expenses']['formatted_amount'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Dual-authorized disbursements</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Net YTD Surplus / Deficit</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">GHS {{ $incomeStatement['net_surplus']['formatted_amount'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Derived strictly from journal lines</p>
        </div>
    </div>

    <!-- Trial Balance + Chart of Accounts / Periods -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-base font-bold text-slate-900">Live Trial Balance (as of {{ $trialBalance['as_of'] }})</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">Code / Account</th>
                            <th class="px-5 py-3 text-right font-semibold">Debit</th>
                            <th class="px-5 py-3 text-right font-semibold">Credit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($trialBalance['accounts'] as $row)
                            <tr>
                                <td class="px-5 py-2.5">
                                    <span class="font-mono font-semibold text-teal-800">{{ $row['code'] }}</span> — {{ $row['name'] }}
                                </td>
                                <td class="px-5 py-2.5 text-right font-mono">{{ $row['debit_pesewas'] > 0 ? $row['debit_formatted'] : '—' }}</td>
                                <td class="px-5 py-2.5 text-right font-mono">{{ $row['credit_pesewas'] > 0 ? $row['credit_formatted'] : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-8 text-center text-slate-400">No posted journal lines yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-base font-bold text-slate-900">Calendar Financial Periods</h2>
            </div>
            <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 text-xs">
                @foreach($periods as $period)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div>
                            <span class="font-mono font-bold text-slate-900">{{ $period->period_key }}</span>
                            <span class="ml-2 text-slate-500 capitalize">({{ $period->period_type }})</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full font-semibold {{ $period->status === 'open' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                {{ $period->status }}
                            </span>
                            @if($period->status === 'open' && auth()->user()->can('accounting.close_period'))
                                <form method="POST" action="{{ route('accounting.periods.close', $period) }}">
                                    @csrf
                                    <button type="submit" class="text-[11px] font-semibold text-rose-700 hover:underline cursor-pointer">Close</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Immutable Posted Journals -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-base font-bold text-slate-900">Immutable Posted Journal Entries</h2>
        </div>
        <div class="divide-y divide-slate-200">
            @forelse($journals as $jrn)
                <div class="p-5 space-y-3 text-xs">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span class="font-mono font-bold text-teal-800">{{ $jrn->journal_number }}</span>
                            <span class="ml-2 text-slate-500">{{ $jrn->entry_date->format('d M Y') }}</span>
                            <p class="font-medium text-slate-900 mt-0.5">{{ $jrn->narration }}</p>
                        </div>
                        <div>
                            @if($jrn->reversingEntry)
                                <span class="px-2.5 py-1 rounded bg-rose-50 text-rose-700 font-semibold">Reversed by {{ $jrn->reversingEntry->journal_number }}</span>
                            @elseif($jrn->reversal_of_journal_id)
                                <span class="px-2.5 py-1 rounded bg-amber-50 text-amber-800 font-semibold">Reversing Entry</span>
                            @elseif(auth()->user()->can('accounting.reverse'))
                                <form method="POST" action="{{ route('accounting.journals.reverse', $jrn) }}" class="flex items-center gap-1.5">
                                    @csrf
                                    <input type="text" name="reason" required placeholder="Reversal reason..." class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs">
                                    <button type="submit" class="rounded-lg bg-rose-600 px-3 py-1 font-semibold text-white cursor-pointer">Reverse</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-100">
                        @foreach($jrn->lines as $line)
                            <div class="flex justify-between font-mono">
                                <span>{{ $line->account?->code }} {{ $line->account?->name }}</span>
                                <span>
                                    @if($line->debit_pesewas > 0)
                                        Dr {{ \App\Support\Shared\Money::fromPesewas($line->debit_pesewas)->format() }}
                                    @else
                                        Cr {{ \App\Support\Shared\Money::fromPesewas($line->credit_pesewas)->format() }}
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-sm text-slate-400">No journals posted yet.</div>
            @endforelse
        </div>
        <div class="px-6 py-3 border-t border-slate-100">
            {{ $journals->links() }}
        </div>
    </div>
@endcomponent
