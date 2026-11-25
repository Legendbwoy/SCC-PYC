@component('layouts.app', [
    'title' => 'Payments & Receipts',
    'heading' => 'Payments, Cash Approvals & Official Receipts',
    'subheading' => 'Paystack online payments, dual-authorized manual cash collection, settlement reconciliation, and PYC-RCT-* receipts.',
])
    <!-- Top Summary & Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="pyc-kpi-green flex flex-col justify-between">
            <div>
                <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3">
                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9"/></svg>
                </div>
                <p class="text-xs font-medium text-slate-500">Total Posted Collections</p>
                <p class="mt-1 text-3xl font-extrabold text-emerald-800">{{ $postedTotal->format() }}</p>
            </div>
            <p class="mt-3 text-xs font-semibold text-emerald-700">
                {{ $awaitingApproval->count() }} cash payment(s) awaiting independent approval
            </p>
        </div>

        @can('payments.record_cash')
            <div class="pyc-card p-6">
                <h2 class="text-sm font-extrabold text-slate-900 mb-3">Record Manual Cash Payment</h2>
                <form method="POST" action="{{ route('payments.cash.store') }}" class="space-y-3">
                    @csrf
                    <select name="member_id" required class="pyc-input py-2 text-xs bg-white">
                        <option value="">— Select Approved Member —</option>
                        @foreach($activeMembers as $m)
                            <option value="{{ $m->id }}">{{ $m->member_number }} — {{ $m->fullName() }}</option>
                        @endforeach
                    </select>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="amount_ghs" required placeholder="Amount GHS (e.g. 10.00)"
                               class="pyc-input py-2 text-xs font-mono">
                        <input type="text" name="notes" placeholder="Cash book ref / note"
                               class="pyc-input py-2 text-xs">
                    </div>
                    <button type="submit" class="btn-pyc-primary w-full py-2 text-xs">
                        Record Cash (Awaits Independent Approval)
                    </button>
                </form>
            </div>
        @endcan

        @can('accounting.post')
            <div class="pyc-card p-6">
                <h2 class="text-sm font-extrabold text-slate-900 mb-3">Record Paystack Bank Settlement</h2>
                <form method="POST" action="{{ route('payments.settlements.store') }}" class="space-y-2.5">
                    @csrf
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="provider_settlement_reference" required placeholder="Settlement Ref"
                               class="pyc-input py-2 text-xs font-mono">
                        <input type="date" name="settlement_date" value="{{ now()->toDateString() }}" required
                               class="pyc-input py-2 text-xs">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="gross_ghs" required placeholder="Gross GHS"
                               class="pyc-input py-2 text-xs font-mono">
                        <input type="text" name="fee_ghs" required value="0.00" placeholder="Fee GHS"
                               class="pyc-input py-2 text-xs font-mono">
                    </div>
                    <button type="submit" class="btn-pyc-outline w-full py-2 text-xs">
                        Post Settlement to Bank &amp; Clearing
                    </button>
                </form>
            </div>
        @endcan
    </div>

    <!-- Maker-Checker Cash Approval Queue -->
    @if($awaitingApproval->isNotEmpty())
        <div class="rounded-2xl bg-amber-50/90 border border-amber-200 p-5 space-y-3">
            <h2 class="text-sm font-extrabold text-amber-950">Cash Payments Awaiting Independent Approval (Maker-Checker)</h2>
            <div class="space-y-2">
                @foreach($awaitingApproval as $pendingCash)
                    <div class="p-4 rounded-xl bg-white border border-amber-200 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div>
                            <p class="font-bold text-slate-900">{{ $pendingCash->reference }} &bull; {{ $pendingCash->amountMoney()->format() }}</p>
                            <p class="text-slate-600">Member: {{ $pendingCash->member?->fullName() }} ({{ $pendingCash->member?->member_number }}) &bull; Recorded by: <strong>{{ $pendingCash->recorder?->name }}</strong></p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(auth()->user()->can('payments.approve') && (int) auth()->id() !== (int) $pendingCash->recorded_by)
                                <form method="POST" action="{{ route('payments.approve', $pendingCash) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 font-bold text-white hover:bg-emerald-700 cursor-pointer">Approve &amp; Post</button>
                                </form>
                                <form method="POST" action="{{ route('payments.reject', $pendingCash) }}" class="flex items-center gap-1">
                                    @csrf
                                    <input type="text" name="reason" required placeholder="Reject reason" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs">
                                    <button type="submit" class="rounded-lg bg-rose-600 px-3 py-1.5 font-bold text-white hover:bg-rose-700 cursor-pointer">Reject</button>
                                </form>
                            @elseif((int) auth()->id() === (int) $pendingCash->recorded_by)
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 font-semibold">Awaiting another officer (Self-approval blocked)</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Payments Register -->
    <div class="pyc-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-extrabold text-slate-900">Payments &amp; Official Receipts Register</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200/80 text-sm">
                <thead class="bg-slate-50/80 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Reference</th>
                        <th class="px-5 py-3 text-left font-semibold">Member</th>
                        <th class="px-5 py-3 text-left font-semibold">Method</th>
                        <th class="px-5 py-3 text-right font-semibold">Amount</th>
                        <th class="px-5 py-3 text-left font-semibold">Lifecycle / Approval</th>
                        <th class="px-5 py-3 text-right font-semibold">Official Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payments as $pay)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3.5 font-mono text-xs font-bold text-[#1E549F]">{{ $pay->reference }}</td>
                            <td class="px-5 py-3.5 text-xs">
                                <p class="font-bold text-slate-900">{{ $pay->member?->fullName() }}</p>
                                <p class="font-mono text-slate-500">{{ $pay->member?->member_number }}</p>
                            </td>
                            <td class="px-5 py-3.5 uppercase text-xs font-bold text-slate-700">{{ $pay->method }}</td>
                            <td class="px-5 py-3.5 text-right font-mono text-xs font-bold text-slate-900">{{ $pay->amountMoney()->format() }}</td>
                            <td class="px-5 py-3.5 text-xs">
                                <span class="px-2.5 py-0.5 rounded-full font-bold {{ $pay->payment_status === 'posted' ? 'bg-emerald-50 text-emerald-700' : ($pay->payment_status === 'failed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-800') }}">
                                    {{ $pay->payment_status }}
                                </span>
                                @if($pay->approval_status !== 'not_required')
                                    <span class="ml-1 text-[11px] text-slate-500">({{ str_replace('_', ' ', $pay->approval_status) }})</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right text-xs">
                                @if($pay->receipt)
                                    <a href="{{ route('payments.receipt.pdf', $pay) }}" class="font-mono font-bold text-[#1E549F] hover:underline">
                                        {{ $pay->receipt->receipt_number }} (PDF)
                                    </a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">No payment records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-slate-100">
            {{ $payments->links() }}
        </div>
    </div>
@endcomponent
