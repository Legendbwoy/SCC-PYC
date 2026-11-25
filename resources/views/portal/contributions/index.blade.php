@component('layouts.app', [
    'title' => 'Dues & Campaigns',
    'heading' => 'Dues, Contribution Campaigns & Operational Arrears',
    'subheading' => 'Confirmed GHS 10.00 (1,000 pesewas) monthly youth dues engine with operational receivable tracking.',
])
    @include('portal.contributions.partials.nav')

    <!-- Operational Totals -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Billed (Expected)</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totals['expected']->format() }}</p>
            <p class="mt-1 text-xs text-slate-500">Operational obligations generated</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Allocated / Collected</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700">{{ $totals['collected']->format() }}</p>
            <p class="mt-1 text-xs text-slate-500">Matched to member obligations</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Waivers &amp; Exemptions</p>
            <p class="mt-2 text-2xl font-bold text-slate-700">{{ $totals['adjusted']->format() }}</p>
            <p class="mt-1 text-xs text-slate-500">Authorized adjustments</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Operational Arrears</p>
            <p class="mt-2 text-2xl font-bold {{ $totals['outstanding']->isPositive() ? 'text-amber-700' : 'text-slate-900' }}">
                {{ $totals['outstanding']->format() }}
            </p>
            <p class="mt-1 text-xs text-slate-500">Not recognized as cash income until paid</p>
        </div>
    </div>

    <!-- Monthly Dues Generator + Create Campaign -->
    @can('dues.generate')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4 h-fit">
                <h2 class="text-base font-bold text-slate-900">Generate Monthly Youth Dues</h2>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Idempotently creates <strong>GHS 10.00 (1,000p)</strong> obligations for all eligible active members for the selected month. Reruns safely skip members already billed.
                </p>
                <form method="POST" action="{{ route('contributions.dues.generate') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Monthly Period (YYYY-MM)</label>
                        <input type="month" name="period" value="{{ old('period', $currentPeriod) }}" required
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono">
                    </div>
                    <button type="submit"
                            class="w-full rounded-xl px-4 py-2.5 text-xs font-semibold text-white cursor-pointer"
                            style="background-color: var(--pyc-primary);">
                        Generate Monthly Obligations
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
                <h2 class="text-base font-bold text-slate-900 mb-1">Create Contribution Campaign</h2>
                <p class="text-xs text-slate-500 mb-4">Shared engine for special collections, harvest levies, event contributions, or future effective-dated dues changes.</p>

                <form method="POST" action="{{ route('contributions.campaigns.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Type</label>
                        <select name="contribution_type_id" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white">
                            @foreach($types as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700">Campaign Title</label>
                        <input type="text" name="title" required placeholder="Youth Week Harvest Levy 2026"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Amount (GHS)</label>
                        <input type="text" name="amount_ghs" required placeholder="20.00"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Frequency</label>
                        <select name="frequency" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white">
                            <option value="one_off">One-Off Campaign</option>
                            <option value="monthly">Monthly Recurring</option>
                            <option value="annual">Annual</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Effective From</label>
                        <input type="date" name="effective_from" value="{{ now()->toDateString() }}" required
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-2 flex items-center">
                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="checkbox" name="generate_obligations_now" value="1" checked class="rounded border-slate-300 text-teal-700">
                            Generate one-off obligations immediately for all currently active members
                        </label>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800 cursor-pointer">
                            Create Campaign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    <!-- Campaigns & Arrears Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-base font-bold text-slate-900">Configured Contribution Campaigns</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">Campaign</th>
                            <th class="px-5 py-3 text-left font-semibold">Rate</th>
                            <th class="px-5 py-3 text-left font-semibold">Effective</th>
                            <th class="px-5 py-3 text-right font-semibold">Obligations</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($campaigns as $camp)
                            <tr>
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-slate-900">{{ $camp->title }}</p>
                                    <p class="text-slate-500 capitalize">{{ $camp->frequency }} &bull; {{ $camp->type?->name }}</p>
                                </td>
                                <td class="px-5 py-3 font-mono font-semibold text-slate-800">{{ $camp->amountMoney()->format() }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $camp->effective_from->format('d M Y') }}</td>
                                <td class="px-5 py-3 text-right font-mono">{{ number_format($camp->obligations_count) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900">Top Member Arrears (Operational)</h2>
                <a href="{{ route('contributions.obligations', ['status' => 'unpaid']) }}" class="text-xs font-semibold text-teal-700">View all &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">Member</th>
                            <th class="px-5 py-3 text-center font-semibold">Open Items</th>
                            <th class="px-5 py-3 text-right font-semibold">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($arrearsSummary as $row)
                            <tr>
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-slate-900">{{ $row['member']->fullName() }}</p>
                                    <p class="font-mono text-teal-800">{{ $row['member']->member_number }}</p>
                                </td>
                                <td class="px-5 py-3 text-center font-mono">{{ $row['obligations_count'] }}</td>
                                <td class="px-5 py-3 text-right font-mono font-bold text-amber-700">{{ $row['outstanding_money']->format() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-8 text-center text-slate-400">No unpaid member obligations recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endcomponent
