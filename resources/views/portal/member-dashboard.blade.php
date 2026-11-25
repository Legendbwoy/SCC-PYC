@component('layouts.app', [
    'title' => 'Member Portal',
    'heading' => 'Welcome, ' . $user->name,
    'subheading' => ($pycBranding['organization_name'] ?? 'St. Cecilia Catholic Church — Parish Youth Council') . ' — Personal Member Portal',
])
    @if(!empty($member))
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs flex flex-wrap items-center justify-between gap-4">
            <div class="space-y-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">Official PYC Membership Card</p>
                <h2 class="text-xl font-bold text-slate-900">{{ $member->fullName() }}</h2>
                <p class="text-sm font-mono font-semibold text-slate-700">{{ $member->member_number }}</p>
                @if($member->groups->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        @foreach($member->groups as $group)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-50 text-teal-800">{{ $group->name }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="flex flex-col items-end gap-2">
                <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold capitalize">
                    {{ $member->status }} Member &bull; Joined {{ $member->joined_at?->format('d M Y') ?? '—' }}
                </span>
                <a href="{{ route('membership.show', $member) }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800">
                    View My Full Membership Record &rarr;
                </a>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Portal Status</p>
            <p class="mt-2 text-xl font-bold text-emerald-700 capitalize">{{ $user->status }}</p>
            <p class="mt-1 text-xs text-slate-500">Scoped strictly to your personal member records</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Standard Monthly Dues</p>
            <p class="mt-2 text-xl font-bold text-slate-900">{{ $monthlyDues->format() }}</p>
            <p class="mt-1 text-xs text-slate-500">Payable online via Paystack or approved cash receipt</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Account Security</p>
            <p class="mt-2 text-xl font-bold text-slate-900">
                {{ $user->hasConfirmedTwoFactorAuthentication() ? '2FA Enabled' : 'Password Protected' }}
            </p>
            <a href="{{ route('profile.security') }}" class="mt-1 inline-block text-xs font-semibold text-teal-700 hover:text-teal-800">
                Update password or enable 2FA &rarr;
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <h2 class="text-base font-bold text-slate-900">My Dues, Payments &amp; Official Receipts</h2>
            <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                Your personal dues statements, Paystack payment checkout, partial/advance allocations, and downloadable <code class="text-teal-800">PYC-RCT-*</code> PDF receipts will appear here once the Contributions &amp; Payments modules are activated.
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <h2 class="text-base font-bold text-slate-900">Parish Youth Events &amp; Preferences</h2>
            <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                Manage your SMS/email communication consent anytime under <a href="{{ route('profile.notifications') }}" class="font-semibold text-teal-700 underline">Alerts &amp; Consent</a>.
            </p>
        </div>
    </div>
@endcomponent
