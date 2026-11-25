@component('layouts.app', [
    'title' => 'My Dues & Statement',
    'heading' => 'My Dues, Contributions & Statement',
    'subheading' => 'Personal statement of monthly youth dues (GHS 10.00 / month), campaign obligations, allocations, and balances.',
])
    @if($member === null || $statement === null)
        <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-sm text-slate-500">
            No PYC membership account is linked to your profile yet.
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Billed</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $statement['total_billed']->format() }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Paid</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ $statement['total_paid']->format() }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Arrears Currently Due</p>
                <p class="mt-2 text-2xl font-bold {{ $statement['arrears_due']->isPositive() ? 'text-amber-700' : 'text-slate-900' }}">
                    {{ $statement['arrears_due']->format() }}
                </p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Net Outstanding</p>
                <p class="mt-2 text-2xl font-bold {{ $statement['total_outstanding']->isPositive() ? 'text-rose-700' : 'text-emerald-700' }}">
                    {{ $statement['total_outstanding']->format() }}
                </p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-base font-bold text-slate-900">Itemized Obligation Ledger ({{ $member->member_number }})</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">Period</th>
                            <th class="px-5 py-3 text-left font-semibold">Contribution</th>
                            <th class="px-5 py-3 text-right font-semibold">Amount Due</th>
                            <th class="px-5 py-3 text-right font-semibold">Paid / Allocated</th>
                            <th class="px-5 py-3 text-right font-semibold">Balance</th>
                            <th class="px-5 py-3 text-left font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($statement['obligations'] as $ob)
                            <tr>
                                <td class="px-5 py-3.5 font-mono text-xs font-semibold text-slate-800">{{ $ob->period_key }}</td>
                                <td class="px-5 py-3.5 text-xs font-medium text-slate-900">{{ $ob->campaign?->title }}</td>
                                <td class="px-5 py-3.5 text-right font-mono text-xs">{{ $ob->dueMoney()->format() }}</td>
                                <td class="px-5 py-3.5 text-right font-mono text-xs text-emerald-700">{{ $ob->allocatedMoney()->format() }}</td>
                                <td class="px-5 py-3.5 text-right font-mono text-xs font-bold {{ $ob->outstandingPesewas() > 0 ? 'text-amber-700' : 'text-slate-600' }}">
                                    {{ $ob->outstandingMoney()->format() }}
                                </td>
                                <td class="px-5 py-3.5 text-xs">
                                    <span class="px-2 py-0.5 rounded-full font-semibold capitalize {{ $ob->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : ($ob->status === 'unpaid' ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                        {{ str_replace('_', ' ', $ob->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-sm text-slate-400">No dues or campaign obligations generated for your account yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endcomponent
