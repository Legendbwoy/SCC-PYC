@component('layouts.app', [
    'title' => 'Member Obligations',
    'heading' => 'Member Obligations, Waivers & Exemptions',
    'subheading' => 'Inspect individual member dues/campaign obligations and record audited waivers, exemptions, or adjustments.',
])
    @include('portal.contributions.partials.nav')

    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
        <form method="GET" action="{{ route('contributions.obligations') }}" class="flex flex-wrap items-center gap-3">
            <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search PYC number or member name..."
                   class="flex-1 min-w-[200px] rounded-xl border border-slate-300 px-3.5 py-2 text-sm">

            <input type="text" name="period" value="{{ $filters['period'] }}" placeholder="Period (e.g. 2026-11)"
                   class="w-44 rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono">

            <select name="status" class="rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white">
                <option value="">All Statuses</option>
                @foreach(['unpaid', 'partially_paid', 'paid', 'waived', 'exempt'] as $st)
                    <option value="{{ $st }}" @selected($filters['status'] === $st)>{{ str_replace('_', ' ', ucfirst($st)) }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white cursor-pointer">
                Filter
            </button>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Member</th>
                        <th class="px-5 py-3 text-left font-semibold">Campaign &amp; Period</th>
                        <th class="px-5 py-3 text-right font-semibold">Billed / Adj / Paid</th>
                        <th class="px-5 py-3 text-right font-semibold">Outstanding</th>
                        <th class="px-5 py-3 text-left font-semibold">Status &amp; Adjustment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($obligations as $ob)
                        <tr class="align-top">
                            <td class="px-5 py-3.5 text-xs">
                                <p class="font-semibold text-slate-900">{{ $ob->member?->fullName() }}</p>
                                <p class="font-mono font-bold text-[#1E549F]">{{ $ob->member?->member_number }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-xs">
                                <p class="font-medium text-slate-800">{{ $ob->campaign?->title }}</p>
                                <p class="font-mono text-slate-500">{{ $ob->period_key }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono text-xs text-slate-600">
                                {{ $ob->dueMoney()->format() }}
                                @if($ob->adjustment_pesewas !== 0)
                                    <span class="block text-[11px] text-slate-500">Adj: {{ \App\Support\Shared\Money::fromPesewas($ob->adjustment_pesewas)->format() }}</span>
                                @endif
                                @if($ob->amount_allocated_pesewas > 0)
                                    <span class="block text-[11px] text-emerald-700">Paid: {{ $ob->allocatedMoney()->format() }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono text-xs font-bold {{ $ob->outstandingPesewas() > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                {{ $ob->outstandingMoney()->format() }}
                            </td>
                            <td class="px-5 py-3.5 text-xs">
                                <span class="px-2 py-0.5 rounded-full font-semibold capitalize {{ $ob->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : ($ob->status === 'unpaid' ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                    {{ str_replace('_', ' ', $ob->status) }}
                                </span>

                                @can('adjust', $ob)
                                    @if($ob->outstandingPesewas() > 0)
                                        <details class="mt-2">
                                            <summary class="cursor-pointer text-[11px] font-bold text-[#1E549F]">Adjust / Waive / Exempt</summary>
                                            <form method="POST" action="{{ route('contributions.obligations.adjust', $ob) }}" class="mt-2 p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                                                @csrf
                                                <select name="adjustment_type" class="block w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs bg-white">
                                                    @can('dues.adjust')
                                                        <option value="waiver">Partial/Full Waiver (-)</option>
                                                    @endcan
                                                    @can('dues.exempt')
                                                        <option value="exemption">Full Exemption (100%)</option>
                                                    @endcan
                                                </select>
                                                <input type="text" name="amount_ghs" placeholder="Amount GHS (blank for full exemption)"
                                                       class="block w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-mono bg-white">
                                                <input type="text" name="reason" required placeholder="Mandatory audit reason..."
                                                       class="block w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs bg-white">
                                                <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1 text-xs font-semibold text-white cursor-pointer">
                                                    Apply
                                                </button>
                                            </form>
                                        </details>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">No obligations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-slate-100">
            {{ $obligations->links() }}
        </div>
    </div>
@endcomponent
