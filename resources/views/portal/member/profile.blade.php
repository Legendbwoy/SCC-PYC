@component('layouts.app', [
    'title' => 'My Member Profile',
    'heading' => 'My PYC Membership Profile',
    'subheading' => 'Review your official St. Cecilia Parish Youth Council registration details and keep your contact information up to date.',
])
    @if($member === null)
        <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-sm text-slate-500">
            No PYC membership record is currently linked to your portal account. Please contact the General Secretary.
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4 h-fit">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">Official Member Number</p>
                    <p class="mt-1 text-2xl font-mono font-bold text-slate-900">{{ $member->member_number }}</p>
                </div>
                <dl class="space-y-2.5 text-xs border-t border-slate-100 pt-4">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Full Name</dt>
                        <dd class="font-semibold text-slate-900">{{ $member->fullName() }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Status</dt>
                        <dd class="font-semibold text-emerald-700 capitalize">{{ $member->status }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Joined Date</dt>
                        <dd class="font-medium text-slate-800">{{ $member->joined_at?->format('d M Y') ?? '—' }}</dd>
                    </div>
                </dl>
                <div class="pt-2 border-t border-slate-100">
                    <p class="text-xs font-semibold text-slate-700 mb-2">Assigned Youth Societies</p>
                    <div class="flex flex-wrap gap-1.5">
                        @forelse($member->groups as $group)
                            <span class="px-2.5 py-1 rounded-full bg-teal-50 text-teal-800 text-xs font-medium">{{ $group->name }}</span>
                        @empty
                            <span class="text-xs text-slate-400">No group assigned yet.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
                <h2 class="text-base font-bold text-slate-900 mb-1">Update Contact &amp; Emergency Details</h2>
                <p class="text-xs text-slate-500 mb-5">Official name and member number changes require General Secretary verification.</p>

                <form method="POST" action="{{ route('member.profile.update') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Phone Number</label>
                        <input type="text" name="phone" value="{{ old('phone', $member->phone) }}"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Occupation / School</label>
                        <input type="text" name="occupation" value="{{ old('occupation', $member->occupation) }}"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700">Residential Address</label>
                        <input type="text" name="address" value="{{ old('address', $member->address) }}"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $member->emergency_contact_name) }}"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Emergency Contact Phone</label>
                        <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $member->emergency_contact_phone) }}"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono">
                    </div>

                    <div class="sm:col-span-2 pt-2">
                        <button type="submit"
                                class="rounded-xl px-5 py-2.5 text-xs font-semibold text-white cursor-pointer"
                                style="background-color: var(--pyc-primary);">
                            Save Contact Details
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endcomponent
