@component('layouts.app', [
    'title' => 'Membership Register',
    'heading' => 'PYC Membership Register & Applications',
    'subheading' => 'Review pending Google Form applications, resolve duplicate candidates, approve members, and issue PYC-YYYY-NNNN invitations.',
])
    @include('portal.membership.partials.nav')

    <!-- Status Summary Pills -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <a href="{{ route('membership.index', ['status' => 'active']) }}" class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs hover:border-teal-500 transition">
            <p class="text-xs text-slate-500 font-semibold uppercase">Active Register</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">{{ number_format($counts['active'] ?? 0) }}</p>
        </a>
        <a href="{{ route('membership.index', ['status' => 'pending']) }}" class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs hover:border-teal-500 transition">
            <p class="text-xs text-slate-500 font-semibold uppercase">Pending Review</p>
            <p class="mt-1 text-2xl font-bold text-amber-700">{{ number_format($counts['pending'] ?? 0) }}</p>
        </a>
        <a href="{{ route('membership.index', ['duplicates_only' => 1]) }}" class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs hover:border-teal-500 transition">
            <p class="text-xs text-slate-500 font-semibold uppercase">Duplicate Flagged</p>
            <p class="mt-1 text-2xl font-bold {{ $duplicateCount > 0 ? 'text-rose-700' : 'text-slate-900' }}">{{ number_format($duplicateCount) }}</p>
        </a>
        <a href="{{ route('membership.index', ['status' => 'rejected']) }}" class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs hover:border-teal-500 transition">
            <p class="text-xs text-slate-500 font-semibold uppercase">Rejected</p>
            <p class="mt-1 text-2xl font-bold text-slate-700">{{ number_format($counts['rejected'] ?? 0) }}</p>
        </a>
        <a href="{{ route('membership.index', ['status' => 'archived']) }}" class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs hover:border-teal-500 transition">
            <p class="text-xs text-slate-500 font-semibold uppercase">Archived</p>
            <p class="mt-1 text-2xl font-bold text-slate-500">{{ number_format($counts['archived'] ?? 0) }}</p>
        </a>
    </div>

    <!-- Filter Bar + Manual Pending Application Drawer -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-4">
        <form method="GET" action="{{ route('membership.index') }}" class="flex flex-wrap items-center gap-3">
            <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search PYC number, name, phone, email..."
                   class="flex-1 min-w-[220px] rounded-xl border border-slate-300 px-3.5 py-2 text-sm">

            <select name="status" class="rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white">
                <option value="">All Statuses</option>
                @foreach(['active', 'pending', 'rejected', 'archived'] as $st)
                    <option value="{{ $st }}" @selected($filters['status'] === $st)>{{ ucfirst($st) }}</option>
                @endforeach
            </select>

            <select name="group_id" class="rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white">
                <option value="">All Youth Groups</option>
                @foreach($groups as $grp)
                    <option value="{{ $grp->id }}" @selected($filters['group_id'] === $grp->id)>{{ $grp->name }}</option>
                @endforeach
            </select>

            <label class="inline-flex items-center gap-1.5 text-xs text-slate-700">
                <input type="checkbox" name="duplicates_only" value="1" @checked($filters['duplicates_only']) class="rounded border-slate-300 text-teal-700">
                Duplicates only
            </label>

            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 cursor-pointer">
                Filter
            </button>

            @if($filters['q'] !== '' || $filters['status'] !== '' || $filters['group_id'] > 0 || $filters['duplicates_only'])
                <a href="{{ route('membership.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-800">Reset</a>
            @endif
        </form>

        @can('members.create')
            <details class="pt-3 border-t border-slate-100">
                <summary class="cursor-pointer text-xs font-bold text-teal-700">+ Record Manual Pending Membership Application</summary>
                <form method="POST" action="{{ route('membership.store') }}" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">First Name *</label>
                        <input type="text" name="first_name" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Middle Name</label>
                        <input type="text" name="middle_name" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Last Name *</label>
                        <input type="text" name="last_name" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Phone (+233 / 024...)</label>
                        <input type="text" name="phone" placeholder="0241234567" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Email Address</label>
                        <input type="email" name="email" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Youth Group / Society</label>
                        <select name="group_name" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white">
                            <option value="">— Optional —</option>
                            @foreach($groups as $grp)
                                <option value="{{ $grp->name }}">{{ $grp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Gender</label>
                        <select name="gender" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white">
                            <option value="">— Select —</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Residential Address</label>
                        <input type="text" name="address" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-3 flex justify-end">
                        <button type="submit" class="rounded-xl px-4 py-2 text-xs font-semibold text-white cursor-pointer" style="background-color: var(--pyc-primary);">
                            Save Pending Application
                        </button>
                    </div>
                </form>
            </details>
        @endcan
    </div>

    <!-- Members Register Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Member No.</th>
                        <th class="px-5 py-3 text-left font-semibold">Full Name &amp; Groups</th>
                        <th class="px-5 py-3 text-left font-semibold">Contact</th>
                        <th class="px-5 py-3 text-left font-semibold">Status &amp; Portal</th>
                        <th class="px-5 py-3 text-right font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($members as $item)
                        <tr>
                            <td class="px-5 py-3.5 whitespace-nowrap font-mono text-xs font-semibold text-teal-800">
                                {{ $item->member_number ?? 'PENDING' }}
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-slate-900">{{ $item->fullName() }}</p>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @if($item->is_duplicate_flagged)
                                        <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 text-[10px] font-bold">POSSIBLE DUPLICATE</span>
                                    @endif
                                    @foreach($item->groups as $grp)
                                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px]">{{ $grp->name }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-600">
                                @if($item->phone)
                                    <p class="font-mono">{{ $item->phone }}</p>
                                @endif
                                @if($item->email)
                                    <p>{{ $item->email }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs">
                                <span class="px-2.5 py-0.5 rounded-full font-semibold capitalize {{ $item->status === 'active' ? 'bg-emerald-50 text-emerald-700' : ($item->status === 'pending' ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $item->status }}
                                </span>
                                @if($item->account)
                                    <p class="mt-1 text-[11px] text-emerald-700 font-medium">Portal Activated</p>
                                @elseif($item->status === 'active')
                                    <p class="mt-1 text-[11px] text-slate-400">Invitation Sent</p>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-right text-xs">
                                <a href="{{ route('membership.show', $item) }}" class="font-semibold text-teal-700 hover:text-teal-800">
                                    Review / View &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">
                                No member records match the current filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-slate-100">
            {{ $members->links() }}
        </div>
    </div>
@endcomponent
