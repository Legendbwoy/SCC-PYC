@component('layouts.app', [
    'title' => 'My Dues & Statement',
    'heading' => 'My Dues & Contributions Statement',
    'subheading' => 'Personal statement of monthly youth dues (GHS 10.00 / month), campaign obligations, allocations, and balances.',
])
    @if($member === null || $statement === null)
        <div class="pyc-card p-8 text-center text-sm text-slate-500">
            No PYC membership account is linked to your profile yet.
        </div>
    @else
        <!-- 4 Pastel KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="pyc-kpi-blue">
                <p class="text-xs font-medium text-slate-500">Total Billed</p>
                <p class="mt-1.5 text-2xl font-extrabold text-slate-900">{{ $statement['total_billed']->format() }}</p>
                <p class="mt-1 text-xs font-semibold text-blue-700">Member {{ $member->member_number }}</p>
            </div>
            <div class="pyc-kpi-green">
                <p class="text-xs font-medium text-slate-500">Total Paid</p>
                <p class="mt-1.5 text-2xl font-extrabold text-emerald-700">{{ $statement['total_paid']->format() }}</p>
                <p class="mt-1 text-xs font-semibold text-emerald-700">Allocated to obligations</p>
            </div>
            <div class="pyc-kpi-amber">
                <p class="text-xs font-medium text-slate-500">Arrears Currently Due</p>
                <p class="mt-1.5 text-2xl font-extrabold {{ $statement['arrears_due']->isPositive() ? 'text-amber-700' : 'text-slate-900' }}">
                    {{ $statement['arrears_due']->format() }}
                </p>
                <p class="mt-1 text-xs font-semibold text-amber-700">Monthly rate: GHS 10.00</p>
            </div>
            <div class="pyc-kpi-purple flex flex-col justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Net Outstanding</p>
                    <p class="mt-1.5 text-2xl font-extrabold {{ $statement['total_outstanding']->isPositive() ? 'text-rose-700' : 'text-emerald-700' }}">
                        {{ $statement['total_outstanding']->format() }}
                    </p>
                </div>
                <a href="{{ route('member.payments') }}" class="mt-2 inline-block text-xs font-bold text-[#1E549F] hover:underline">
                    Pay Now with Paystack &rarr;
                </a>
            </div>
        </div>

        <!-- Itemized Ledger -->
        <div class="pyc-card overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-base font-extrabold text-slate-900">Itemized Obligation Ledger ({{ $member->member_number }})</h2>
                <a href="{{ route('member.payments') }}" class="btn-pyc-primary py-2 px-4 text-xs">
                    Pay Dues Online
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200/80 text-sm">
                    <thead class="bg-slate-50/80 text-xs uppercase text-slate-500">
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
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3.5 font-mono text-xs font-bold text-[#0F2D59]">{{ $ob->period_key }}</td>
                                <td class="px-5 py-3.5 text-xs font-semibold text-slate-900">{{ $ob->campaign?->title }}</td>
                                <td class="px-5 py-3.5 text-right font-mono text-xs">{{ $ob->dueMoney()->format() }}</td>
                                <td class="px-5 py-3.5 text-right font-mono text-xs font-semibold text-emerald-700">{{ $ob->allocatedMoney()->format() }}</td>
                                <td class="px-5 py-3.5 text-right font-mono text-xs font-bold {{ $ob->outstandingPesewas() > 0 ? 'text-amber-700' : 'text-slate-600' }}">
                                    {{ $ob->outstandingMoney()->format() }}
                                </td>
                                <td class="px-5 py-3.5 text-xs">
                                    <span class="px-2.5 py-0.5 rounded-full font-bold capitalize {{ $ob->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : ($ob->status === 'unpaid' ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                        {{ str_replace('_', ' ', $ob->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">No dues or campaign obligations generated for your account yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endcomponent
