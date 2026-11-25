@component('layouts.app', [
    'title' => 'Payment & Dues',
    'heading' => 'Payment & Dues',
    'subheading' => 'Manage your contributions, pay securely via Paystack, and download your official PYC-RCT-* PDF receipts.',
])
    @if($member === null)
        <div class="pyc-card p-8 text-center text-sm text-slate-500">
            No PYC membership account is linked to your profile yet.
        </div>
    @else
        @php
            $hasOutstanding = $statement && $statement['total_outstanding']->isPositive();
            $defaultPayAmount = $hasOutstanding ? $statement['total_outstanding']->formattedAmount() : '10.00';
        @endphp

        <!-- Spotlight Row: Monthly Dues Checkout + Payment Methods (Matching Bottom-Center Reference Mockup) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: Monthly Dues & Paystack Checkout -->
            <div class="lg:col-span-6 pyc-card p-6 sm:p-7 flex flex-col justify-between space-y-5">
                <div>
                    <p class="text-sm font-bold text-slate-700">
                        {{ $hasOutstanding ? 'Outstanding Dues & Contributions' : 'Monthly Dues' }}
                    </p>
                    <p class="mt-2 text-4xl font-extrabold tracking-tight text-slate-900">
                        {{ $hasOutstanding ? $statement['total_outstanding']->format() : 'GHS 10.00' }}
                    </p>
                    <p class="mt-1.5 text-xs font-bold {{ $hasOutstanding ? 'text-amber-600' : 'text-emerald-600' }}">
                        {{ $hasOutstanding ? 'Due: ' . now()->endOfMonth()->format('M j, Y') : 'Status: Up to Date (Advance payment enabled)' }}
                    </p>
                </div>

                <form method="POST" action="{{ route('member.payments.initialize') }}" class="space-y-3.5 pt-3 border-t border-slate-100">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Amount to Pay (GHS)</label>
                        <input type="text" name="amount_ghs" required value="{{ $defaultPayAmount }}"
                               class="pyc-input font-mono text-base font-bold">
                        <p class="mt-1 text-[11px] text-slate-500">Supports partial arrears payments and multi-month advance payments.</p>
                    </div>
                    <button type="submit" class="btn-pyc-primary w-full py-3 text-sm">
                        <svg class="h-4.5 w-4.5 text-sky-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                        <span>Pay Now with Paystack</span>
                    </button>
                </form>
            </div>

            <!-- Right: Payment Methods Info Card -->
            <div class="lg:col-span-6 pyc-card p-6 sm:p-7 space-y-4">
                <h2 class="text-base font-extrabold text-slate-900">Payment Methods</h2>

                <div class="space-y-3">
                    <div class="p-4 rounded-2xl bg-sky-50/70 border border-sky-100 flex items-center gap-4">
                        <div class="h-11 w-11 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0">
                            <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-extrabold text-slate-900">Paystack</p>
                            <p class="text-xs text-slate-600">Card, Mobile Money (MTN, Telecel, AT) — Instant Posting</p>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-100 flex items-center gap-4">
                        <div class="h-11 w-11 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0">
                            <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-extrabold text-slate-900">Cash</p>
                            <p class="text-xs text-slate-600">Recorded by Financial Secretary / Treasurer &bull; Awaits Approval</p>
                        </div>
                    </div>
                </div>

                <div class="pt-1">
                    <a href="{{ route('member.dues') }}" class="text-xs font-bold text-[#1E549F] hover:underline">
                        View itemized monthly dues statement &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- Automatic Official Receipt Info Banner (Matching Reference Mockup) -->
        <div class="rounded-2xl bg-sky-50 border border-sky-200/80 px-5 py-3.5 flex items-center justify-center gap-2.5 text-xs sm:text-sm font-semibold text-[#0F2D59]">
            <span class="h-5 w-5 rounded-full bg-[#1E549F] text-white flex items-center justify-center text-xs font-bold shrink-0">i</span>
            <span>Your payment will generate an official <code class="font-mono">PYC-RCT-*</code> receipt automatically.</span>
        </div>

        <!-- Payment & Official Receipt History -->
        <div class="pyc-card overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-base font-extrabold text-slate-900">My Payment &amp; Receipt History</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200/80 text-sm">
                    <thead class="bg-slate-50/80 text-xs uppercase text-slate-500">
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
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3.5 font-mono text-xs font-bold text-[#0F2D59]">{{ $pay->reference }}</td>
                                <td class="px-5 py-3.5 uppercase text-xs font-semibold text-slate-700">{{ $pay->method }}</td>
                                <td class="px-5 py-3.5 text-right font-mono text-xs font-bold text-slate-900">{{ $pay->amountMoney()->format() }}</td>
                                <td class="px-5 py-3.5 text-xs">
                                    <span class="px-2.5 py-0.5 rounded-full font-bold capitalize {{ $pay->payment_status === 'posted' ? 'bg-emerald-50 text-emerald-700' : ($pay->payment_status === 'failed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-800') }}">
                                        {{ $pay->payment_status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right text-xs">
                                    @if($pay->receipt)
                                        <a href="{{ route('payments.receipt.pdf', $pay) }}" class="inline-flex items-center gap-1 font-mono font-bold text-[#1E549F] hover:underline">
                                            <span>{{ $pay->receipt->receipt_number }}</span>
                                            <span>(PDF)</span>
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
    @endif
@endcomponent
