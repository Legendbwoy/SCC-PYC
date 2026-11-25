@component('layouts.app', [
    'title' => 'My Profile',
    'heading' => 'My Member Profile',
    'subheading' => 'Review your official St. Cecilia Parish Youth Council registration details and keep your contact information up to date.',
])
    @if($member === null)
        <div class="pyc-card p-8 text-center text-sm text-slate-500">
            No PYC membership record is currently linked to your portal account. Please contact the General Secretary.
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Official Membership Card -->
            <div class="rounded-2xl bg-gradient-to-br from-[#081F3F] via-[#0F2D59] to-[#1A4B8C] text-white p-6 shadow-md space-y-5 h-fit">
                <div class="flex items-center justify-between">
                    <x-pyc-logo variant="dark" size="sm" subtitle="Official Member Card" />
                    <span class="px-2.5 py-1 rounded-full bg-emerald-400/20 text-emerald-200 text-[11px] font-bold capitalize">
                        {{ $member->status }}
                    </span>
                </div>

                <div class="pt-1">
                    <p class="text-[11px] uppercase tracking-wider text-sky-200/80 font-semibold">Member Number</p>
                    <p class="mt-0.5 text-2xl font-mono font-extrabold tracking-wide text-[#F5C542]">{{ $member->member_number }}</p>
                </div>

                <dl class="space-y-2 text-xs border-t border-white/15 pt-4">
                    <div class="flex justify-between">
                        <dt class="text-sky-200/80">Full Name</dt>
                        <dd class="font-bold text-white">{{ $member->fullName() }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sky-200/80">Joined Date</dt>
                        <dd class="font-semibold text-white">{{ $member->joined_at?->format('d M Y') ?? '—' }}</dd>
                    </div>
                </dl>

                <div class="pt-3 border-t border-white/15">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-sky-200/80 mb-2">Assigned Youth Societies</p>
                    <div class="flex flex-wrap gap-1.5">
                        @forelse($member->groups as $group)
                            <span class="px-2.5 py-1 rounded-full bg-white/15 text-white text-xs font-semibold">{{ $group->name }}</span>
                        @empty
                            <span class="text-xs text-sky-200/70">General Parish Youth Council</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Update Contact & Emergency Details Form -->
            <div class="lg:col-span-2 pyc-card p-6 sm:p-7">
                <h2 class="text-lg font-extrabold text-slate-900 mb-1">Contact &amp; Emergency Details</h2>
                <p class="text-xs text-slate-500 mb-5">Official name and member number changes require General Secretary verification.</p>

                <form method="POST" action="{{ route('member.profile.update') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number</label>
                        <input type="text" name="phone" value="{{ old('phone', $member->phone) }}" class="pyc-input font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Occupation / School</label>
                        <input type="text" name="occupation" value="{{ old('occupation', $member->occupation) }}" class="pyc-input">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Residential Address</label>
                        <input type="text" name="address" value="{{ old('address', $member->address) }}" class="pyc-input">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $member->emergency_contact_name) }}" class="pyc-input">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Emergency Contact Phone</label>
                        <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $member->emergency_contact_phone) }}" class="pyc-input font-mono">
                    </div>

                    <div class="sm:col-span-2 pt-2">
                        <button type="submit" class="btn-pyc-primary px-6">
                            Save Contact Details
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endcomponent
