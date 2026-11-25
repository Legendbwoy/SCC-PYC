@component('layouts.app', [
    'title' => 'Member Portal',
    'heading' => 'Welcome, ' . $user->name,
    'subheading' => ($pycBranding['organization_name'] ?? 'St. Cecilia Catholic Church — Parish Youth Council') . ' — Personal Member Portal',
])
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
                Your personal dues statements, Paystack payment checkout, partial/advance allocations, and downloadable <code class="text-teal-800">PYC-RCT-*</code> PDF receipts will appear here once Membership &amp; Contributions modules are activated.
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <h2 class="text-base font-bold text-slate-900">Parish Youth Events &amp; Preferences</h2>
            <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                Upcoming St. Cecilia PYC events, one-click event registrations, and SMS/email communication consent preferences will be available here in upcoming milestones.
            </p>
        </div>
    </div>
@endcomponent
