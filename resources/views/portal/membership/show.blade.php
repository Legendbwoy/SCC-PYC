@component('layouts.app', [
    'title' => $member->fullName(),
    'heading' => $member->fullName() . ($member->member_number ? " ({$member->member_number})" : ' — Pending Application'),
    'subheading' => 'Membership Record, Secretary Review, Portal Activation & Audit History',
])
    @if(auth()->user()->isExecutiveOfficer())
        @include('portal.membership.partials.nav')
    @endif

    @if($member->is_duplicate_flagged && $duplicateCandidates->isNotEmpty())
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-5 space-y-3">
            <h2 class="text-sm font-bold text-rose-900">Possible Duplicate Candidate(s) Detected — Human Review Required</h2>
            <p class="text-xs text-rose-800">
                This application shares a normalized phone number, email address, or full name with existing record(s). Please verify before approving or rejecting:
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($duplicateCandidates as $candidate)
                    <div class="p-3 rounded-xl bg-white border border-rose-200 text-xs flex justify-between items-center">
                        <div>
                            <p class="font-bold text-slate-900">{{ $candidate->fullName() }} ({{ $candidate->member_number ?? strtoupper($candidate->status) }})</p>
                            <p class="text-slate-600 font-mono">{{ $candidate->phone }} &bull; {{ $candidate->email }}</p>
                        </div>
                        @can('view', $candidate)
                            <a href="{{ route('membership.show', $candidate) }}" class="font-bold text-[#1E549F] underline">Inspect</a>
                        @endcan
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Member Details -->
        <div class="lg:col-span-2 pyc-card p-6 space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3.5">
                    @if($member->photoUrl())
                        <img src="{{ $member->photoUrl() }}" alt="{{ $member->fullName() }}" class="h-14 w-14 rounded-2xl object-cover ring-2 ring-[#0F2D59]/15 shrink-0">
                    @else
                        <div class="h-14 w-14 rounded-2xl bg-sky-100 text-[#0F2D59] font-extrabold text-base flex items-center justify-center shrink-0">
                            {{ strtoupper(substr($member->first_name, 0, 1) . substr($member->last_name, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <p class="text-xs text-slate-500 uppercase font-semibold">Official Member Number</p>
                        <p class="text-lg font-mono font-bold text-[#0F2D59]">{{ $member->member_number ?? 'Assigned upon approval' }}</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold capitalize {{ $member->status === 'active' ? 'bg-emerald-50 text-emerald-700' : ($member->status === 'pending' ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                    Status: {{ $member->status }}
                </span>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs text-slate-500">Normalized Phone</dt>
                    <dd class="font-mono font-medium text-slate-900">{{ $member->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Normalized Email</dt>
                    <dd class="font-medium text-slate-900">{{ $member->email ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Date of Birth / Gender</dt>
                    <dd class="font-medium text-slate-900">{{ $member->date_of_birth?->format('d M Y') ?? '—' }} &bull; {{ ucfirst($member->gender ?? '—') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Joined Date</dt>
                    <dd class="font-medium text-slate-900">{{ $member->joined_at?->format('d M Y') ?? 'Pending' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Residential Address</dt>
                    <dd class="font-medium text-slate-900">{{ $member->address ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Occupation / School</dt>
                    <dd class="font-medium text-slate-900">{{ $member->occupation ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-slate-500">Assigned Youth Groups / Societies</dt>
                    <dd class="mt-1 flex flex-wrap gap-1.5">
                        @forelse($member->groups as $group)
                            <span class="px-2.5 py-1 rounded-full bg-sky-50 text-[#0F2D59] text-xs font-semibold">{{ $group->name }}</span>
                        @empty
                            <span class="text-xs text-slate-400">No group assigned yet.</span>
                        @endforelse
                    </dd>
                </div>
            </dl>

            @can('update', $member)
                <form method="POST" action="{{ route('membership.photo', $member) }}" enctype="multipart/form-data" class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    @csrf
                    <div>
                        <p class="text-xs font-bold text-slate-800">Member Portrait Photo</p>
                        <input type="file" name="photo" required accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                               class="mt-1 block text-xs text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-[#0F2D59] file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                    </div>
                    <button type="submit" class="btn-pyc-primary py-2 px-4 text-xs">Upload Photo</button>
                </form>

                <details class="pt-4 border-t border-slate-100">
                    <summary class="cursor-pointer text-xs font-bold text-[#1E549F]">Edit Member Details / Assign Youth Group</summary>
                    <form method="POST" action="{{ route('membership.update', $member) }}" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">First Name</label>
                            <input type="text" name="first_name" value="{{ $member->first_name }}" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Last Name</label>
                            <input type="text" name="last_name" value="{{ $member->last_name }}" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Phone</label>
                            <input type="text" name="phone" value="{{ $member->phone }}" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Email</label>
                            <input type="email" name="email" value="{{ $member->email }}" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Address</label>
                            <input type="text" name="address" value="{{ $member->address }}" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Add to Youth Group</label>
                            <select name="member_group_id" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white">
                                <option value="">— No change —</option>
                                @foreach($availableGroups as $grp)
                                    <option value="{{ $grp->id }}">{{ $grp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white cursor-pointer">Save Changes</button>
                        </div>
                    </form>
                </details>
            @endcan
        </div>

        <!-- Secretary Decision & Portal Account Card -->
        <div class="space-y-6">
            @can('approve', $member)
                @if($member->status === 'pending' || $member->status === 'rejected')
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4">
                        <h2 class="text-base font-bold text-slate-900">Secretary Review Decision</h2>

                        <form method="POST" action="{{ route('membership.approve', $member) }}" class="space-y-2.5">
                            @csrf
                            <input type="text" name="reason" placeholder="Optional approval note..."
                                   class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            <button type="submit" class="btn-pyc-primary w-full text-xs">
                                Approve, Issue PYC Number &amp; Invite
                            </button>
                        </form>

                        @if($member->status === 'pending')
                            <form method="POST" action="{{ route('membership.reject', $member) }}" class="pt-3 border-t border-slate-100 space-y-2.5">
                                @csrf
                                <label class="block text-xs font-semibold text-rose-800">Reject Application (Reason Required)</label>
                                <textarea name="reason" rows="2" required placeholder="State clear reason for rejection..."
                                          class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-xs"></textarea>
                                <button type="submit" class="w-full rounded-xl border border-rose-300 bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100 cursor-pointer">
                                    Reject Application
                                </button>
                            </form>
                        @endif
                    </div>
                @endif

                @if($member->status === 'active' && !$member->account)
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-3">
                        <h2 class="text-base font-bold text-slate-900">Portal Invitation</h2>
                        <p class="text-xs text-slate-600">Member has not yet activated their portal account. Issuing a new invitation revokes any previous unused token.</p>
                        <form method="POST" action="{{ route('membership.invite', $member) }}">
                            @csrf
                            <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800 cursor-pointer">
                                Issue New Single-Use Invitation
                            </button>
                        </form>
                    </div>
                @endif
            @endcan

            @if($member->status === 'active')
                @can('archive', $member)
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-3">
                        <h2 class="text-sm font-bold text-slate-900">Archive Member</h2>
                        <form method="POST" action="{{ route('membership.archive', $member) }}" class="space-y-2.5">
                            @csrf
                            <input type="text" name="reason" required placeholder="Reason (e.g. relocated from parish)"
                                   class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            <button type="submit" class="w-full rounded-xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                                Archive Member Record
                            </button>
                        </form>
                    </div>
                @endcan
            @endif
        </div>
    </div>

    <!-- Traceability: Raw Source Submission & Status History -->
    @if(auth()->user()->isExecutiveOfficer())
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-3">
                <h2 class="text-base font-bold text-slate-900">Membership Status History</h2>
                <div class="space-y-2">
                    @foreach($member->statusHistories as $hist)
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs flex justify-between gap-2">
                            <div>
                                <p class="font-semibold text-slate-900">
                                    {{ $hist->from_status ? ucfirst($hist->from_status).' → ' : '' }}{{ ucfirst($hist->to_status) }}
                                </p>
                                <p class="text-slate-600">{{ $hist->reason }}</p>
                            </div>
                            <div class="text-right text-slate-400 shrink-0">
                                <p>{{ $hist->created_at?->timezone('Africa/Accra')->format('d M Y H:i') }}</p>
                                <p>{{ $hist->actor?->name ?? 'System' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-3">
                <h2 class="text-base font-bold text-slate-900">Original Submission Payload(s)</h2>
                @forelse($member->submissions as $sub)
                    <div class="space-y-1.5 text-xs">
                        <p class="font-mono font-bold text-[#1E549F]">{{ $sub->source }} &bull; {{ $sub->external_response_id }}</p>
                        <pre class="p-3 rounded-xl bg-slate-900 text-slate-100 text-[11px] overflow-x-auto">{{ json_encode($sub->raw_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                @empty
                    <p class="text-xs text-slate-400">Created directly via executive officer entry.</p>
                @endforelse
            </div>
        </div>
    @endif
@endcomponent
