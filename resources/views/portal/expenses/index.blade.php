@component('layouts.app', [
    'title' => 'Expenses',
    'heading' => 'Parish Youth Council Expenses & Dual Authorization',
    'subheading' => 'Submit council expenditure vouchers, enforce independent officer approval, and disburse to double-entry ledger.',
])
    @can('expenses.create')
        <div class="pyc-card p-6">
            <h2 class="text-base font-extrabold text-slate-900 mb-4">Record &amp; Submit Expense Voucher</h2>
            <form method="POST" action="{{ route('expenses.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Expense Category</label>
                    <select name="expense_category_id" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->account?->code }} — {{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Payee / Vendor Name</label>
                    <input type="text" name="payee_name" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Amount (GHS)</label>
                    <input type="text" name="amount_ghs" required placeholder="50.00" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Expense Date</label>
                    <input type="date" name="expense_date" value="{{ now()->toDateString() }}" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Disbursement Channel</label>
                    <select name="payment_channel" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white">
                        <option value="cash">Cash in Hand (1010)</option>
                        <option value="bank">PYC Bank Account (1020)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Purpose / Narration</label>
                    <input type="text" name="description" required placeholder="Transport for deanery youth rally" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="sm:col-span-3 flex justify-end">
                    <button type="submit" class="btn-pyc-primary py-2.5 px-5 text-xs">
                        Submit Expense for Independent Approval
                    </button>
                </div>
            </form>
        </div>
    @endcan

    <div class="pyc-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-extrabold text-slate-900">Expense Register &amp; Approval Queue</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Reference</th>
                        <th class="px-5 py-3 text-left font-semibold">Payee &amp; Description</th>
                        <th class="px-5 py-3 text-right font-semibold">Amount</th>
                        <th class="px-5 py-3 text-left font-semibold">Status &amp; Officers</th>
                        <th class="px-5 py-3 text-right font-semibold">Workflow Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($expenses as $exp)
                        <tr>
                            <td class="px-5 py-3.5 font-mono text-xs font-bold text-[#1E549F]">
                                {{ $exp->reference }}
                                <span class="block text-[11px] text-slate-400">{{ $exp->expense_date->format('d M Y') }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-xs">
                                <p class="font-semibold text-slate-900">{{ $exp->payee_name }} ({{ $exp->category?->name }})</p>
                                <p class="text-slate-600">{{ $exp->description }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono text-xs font-bold text-slate-900">{{ $exp->amountMoney()->format() }}</td>
                            <td class="px-5 py-3.5 text-xs">
                                <span class="px-2 py-0.5 rounded-full font-semibold capitalize {{ $exp->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : ($exp->status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-800') }}">
                                    {{ $exp->status }}
                                </span>
                                <p class="mt-1 text-[11px] text-slate-500">By: {{ $exp->submitter?->name ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-right text-xs">
                                @if($exp->status === 'submitted')
                                    @if(auth()->user()->can('expenses.approve') && (int) auth()->id() !== (int) ($exp->submitted_by ?? $exp->created_by))
                                        <div class="inline-flex items-center gap-1.5">
                                            <form method="POST" action="{{ route('expenses.approve', $exp) }}">
                                                @csrf
                                                <button type="submit" class="rounded-lg bg-emerald-700 px-3 py-1.5 font-semibold text-white cursor-pointer">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('expenses.reject', $exp) }}" class="inline-flex gap-1">
                                                @csrf
                                                <input type="text" name="reason" required placeholder="Reason" class="w-28 rounded-lg border border-slate-300 px-2 py-1 text-xs">
                                                <button type="submit" class="rounded-lg bg-rose-600 px-2.5 py-1.5 font-semibold text-white cursor-pointer">Reject</button>
                                            </form>
                                        </div>
                                    @elseif((int) auth()->id() === (int) ($exp->submitted_by ?? $exp->created_by))
                                        <span class="text-slate-400">Awaiting independent officer</span>
                                    @endif
                                @elseif($exp->status === 'approved' && auth()->user()->can('accounting.post'))
                                    <form method="POST" action="{{ route('expenses.pay', $exp) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 font-semibold text-white cursor-pointer">Disburse &amp; Post</button>
                                    </form>
                                @elseif($exp->journalEntry)
                                    <span class="font-mono text-emerald-700">{{ $exp->journalEntry->journal_number }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">No expenses recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endcomponent
