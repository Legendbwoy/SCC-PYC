@component('layouts.app', [
    'title' => 'My Payments & Receipts',
    'heading' => 'My Payments & Official Receipts',
    'subheading' => 'Pay monthly dues or advance contributions securely via Paystack and download your official PYC-RCT-* PDF receipts.',
])
    @if($member === null)
        <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-sm text-slate-500">
            No PYC membership account is linked to your profile yet.
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4 h-fit">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Current Outstanding Balance</p>
                    <p class="mt-1 text-2xl font-bold {{ ($statement['total_outstanding'] ?? null)?->isPositive() ? 'text-amber-700' : 'text-emerald-700' }}">
                        {{ $statement ? $statement['total_outstanding']->format() : 'GHS 0.00' }}
                    </p>
                </div>

                <form method="POST" action="{{ route('member.payments.initialize') }}" class="space-y-3 pt-3 border-t border-slate-100">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Pay Online via Paystack (GHS)</label>
                        <input type="text" name="amount_ghs" required
                               value="{{ ($statement && $statement['total_outstanding']->isPositive()) ? $statement['total_outstanding']->formattedAmount() : '10.00' }}"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono">
                        <p class="mt-1 text-[11px] text-slate-500">Supports partial arrears payments and multi-month advance payments.</p>
                    </div>
                    <button type="submit" class="w-full rounded-xl px-4 py-2.5 text-xs font-semibold text-white cursor-pointer" style="background-color: var(--pyc-primary);">
                        Proceed to Secure Paystack Checkout
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h2 class="text-base font-bold text-slate-900">My Payment &amp; Receipt History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold">Reference</th>
                                <th class="px-5 py-3 text-left font-semibold">Method</th>
                                <th class="px-5 py-3 text-right font-semibold">Amount</th>
                                <th class="px-5 py-3 text-left font-semibold">Status</th>
                                <th class="px-5 py-3 text-right font-semibold">Official Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($payments as $pay)
                                <tr>
                                    <td class="px-5 py-3.5 font-mono text-xs font-semibold text-teal-800">{{ $pay->reference }}</td>
                                    <td class="px-5 py-3.5 uppercase text-xs text-slate-700">{{ $pay->method }}</td>
                                    <td class="px-5 py-3.5 text-right font-mono text-xs font-bold">{{ $pay->amountMoney()->format() }}</td>
                                    <td class="px-5 py-3.5 text-xs capitalize">{{ $pay->payment_status }}</td>
                                    <td class="px-5 py-3.5 text-right text-xs">
                                        @if($pay->receipt)
                                            <a href="{{ route('payments.receipt.pdf', $pay) }}" class="font-mono font-semibold text-teal-700 hover:underline">
                                                {{ $pay->receipt->receipt_number }} (PDF)
                                            </a>
                                        @else
                                            <span class="text-slate-400">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">No payments recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endcomponent
