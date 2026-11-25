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
                <a href="{{ route('member.profile') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800">
                    Manage My Member Profile &rarr;
                </a>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">My Outstanding Dues</p>
            <p class="mt-2 text-xl font-bold {{ ($statement['total_outstanding'] ?? null)?->isPositive() ? 'text-amber-700' : 'text-emerald-700' }}">
                {{ isset($statement['total_outstanding']) ? $statement['total_outstanding']->format() : 'GHS 0.00' }}
            </p>
            <a href="{{ route('member.dues') }}" class="mt-1 inline-block text-xs font-semibold text-teal-700 hover:text-teal-800">
                View full personal statement &rarr;
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Standard Monthly Dues</p>
            <p class="mt-2 text-xl font-bold text-slate-900">{{ $monthlyDues->format() }}</p>
            <p class="mt-1 text-xs text-slate-500">1,000 pesewas / month (partial &amp; advance supported)</p>
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
@endcomponent
