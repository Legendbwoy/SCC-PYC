@component('layouts.app', [
    'title' => 'Payments & Receipts',
    'heading' => 'Payments, Maker-Checker Cash Approvals & Official Receipts',
    'subheading' => 'Paystack online payments, dual-authorized manual cash collection, settlement reconciliation, and PYC-RCT-* receipts.',
])
    <!-- Top Summary & Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs flex flex-col justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Posted Collections</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ $postedTotal->format() }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $awaitingApproval->count() }} cash payment(s) awaiting independent approval</p>
            </div>
        </div>

        @can('payments.record_cash')
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
                <h2 class="text-sm font-bold text-slate-900 mb-3">Record Manual Cash Payment</h2>
                <form method="POST" action="{{ route('payments.cash.store') }}" class="space-y-3">
                    @csrf
                    <select name="member_id" required class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white">
                        <option value="">— Select Approved Member —</option>
                        @foreach($activeMembers as $m)
                            <option value="{{ $m->id }}">{{ $m->member_number }} — {{ $m->fullName() }}</option>
                        @endforeach
                    </select>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="amount_ghs" required placeholder="Amount GHS (e.g. 10.00)"
                               class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-xs font-mono">
                        <input type="text" name="notes" placeholder="Cash book ref / note"
                               class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                    </div>
                    <button type="submit" class="w-full rounded-xl px-4 py-2 text-xs font-semibold text-white cursor-pointer" style="background-color: var(--pyc-primary);">
                        Record Cash (Awaits Independent Approval)
                    </button>
                </form>
            </div>
        @endcan

        @can('accounting.post')
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
                <h2 class="text-sm font-bold text-slate-900 mb-3">Record Paystack Bank Settlement</h2>
                <form method="POST" action="{{ route('payments.settlements.store') }}" class="space-y-2.5">
                    @csrf
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="provider_settlement_reference" required placeholder="Settlement Ref"
                               class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-xs font-mono">
                        <input type="date" name="settlement_date" value="{{ now()->toDateString() }}" required
                               class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="gross_ghs" required placeholder="Gross GHS"
                               class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-xs font-mono">
                        <input type="text" name="fee_ghs" required value="0.00" placeholder="Fee GHS"
                               class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-xs font-mono">
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white cursor-pointer">
                        Post Settlement to Bank &amp; Clearing
                    </button>
                </form>
            </div>
        @endcan
    </div>

    <!-- Maker-Checker Cash Approval Queue -->
    @if($awaitingApproval->isNotEmpty())
        <div class="bg-amber-50/70 rounded-2xl border border-amber-200 p-5 space-y-3">
            <h2 class="text-sm font-bold text-amber-950">Cash Payments Awaiting Independent Approval (Maker-Checker)</h2>
            <div class="space-y-2">
                @foreach($awaitingApproval as $pendingCash)
                    <div class="p-3.5 rounded-xl bg-white border border-amber-200 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div>
                            <p class="font-bold text-slate-900">{{ $pendingCash->reference }} &bull; {{ $pendingCash->amountMoney()->format() }}</p>
                            <p class="text-slate-600">Member: {{ $pendingCash->member?->fullName() }} ({{ $pendingCash->member?->member_number }}) &bull; Recorded by: <strong>{{ $pendingCash->recorder?->name }}</strong></p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(auth()->user()->can('payments.approve') && (int) auth()->id() !== (int) $pendingCash->recorded_by)
                                <form method="POST" action="{{ route('payments.approve', $pendingCash) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-emerald-700 px-3 py-1.5 font-semibold text-white cursor-pointer">Approve &amp; Post</button>
                                </form>
                                <form method="POST" action="{{ route('payments.reject', $pendingCash) }}" class="flex items-center gap-1">
                                    @csrf
                                    <input type="text" name="reason" required placeholder="Reject reason" class="rounded-lg border border-slate-300 px-2 py-1 text-xs">
                                    <button type="submit" class="rounded-lg bg-rose-600 px-3 py-1.5 font-semibold text-white cursor-pointer">Reject</button>
                                </form>
                            @elseif((int) auth()->id() === (int) $pendingCash->recorded_by)
                                <span class="px-2.5 py-1 rounded bg-slate-100 text-slate-600 font-medium">Awaiting another officer (Self-approval blocked)</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Payments Register -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-base font-bold text-slate-900">Payments &amp; Official Receipts Register</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
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
                        <tr>
                            <td class="px-5 py-3.5 font-mono text-xs font-semibold text-teal-800">{{ $pay->reference }}</td>
                            <td class="px-5 py-3.5 text-xs">
                                <p class="font-semibold text-slate-900">{{ $pay->member?->fullName() }}</p>
                                <p class="font-mono text-slate-500">{{ $pay->member?->member_number }}</p>
                            </td>
                            <td class="px-5 py-3.5 uppercase text-xs font-semibold text-slate-700">{{ $pay->method }}</td>
                            <td class="px-5 py-3.5 text-right font-mono text-xs font-bold text-slate-900">{{ $pay->amountMoney()->format() }}</td>
                            <td class="px-5 py-3.5 text-xs">
                                <span class="px-2 py-0.5 rounded-full font-semibold {{ $pay->payment_status === 'posted' ? 'bg-emerald-50 text-emerald-700' : ($pay->payment_status === 'failed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-800') }}">
                                    {{ $pay->payment_status }}
                                </span>
                                @if($pay->approval_status !== 'not_required')
                                    <span class="ml-1 text-[11px] text-slate-500">({{ str_replace('_', ' ', $pay->approval_status) }})</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right text-xs">
                                @if($pay->receipt)
                                    <a href="{{ route('payments.receipt.pdf', $pay) }}" class="font-mono font-semibold text-teal-700 hover:underline">
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
