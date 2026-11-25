@component('layouts.app', [
    'title' => 'Security & Settings',
    'heading' => 'Account Settings & Two-Factor Authentication',
    'subheading' => 'Manage your profile information, strong password, and TOTP authenticator security.',
])
    <!-- Two-Factor Authentication Card -->
    <div class="pyc-card p-6 sm:p-7 space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-extrabold text-slate-900">Two-Factor Authentication (TOTP)</h2>
                <p class="mt-1 text-xs sm:text-sm text-slate-600">
                    @if($user->requiresTwoFactor())
                        Mandatory for all PYC executive and administrative officers (Google Authenticator, Microsoft Authenticator, Authy, or 1Password).
                    @else
                        Add an extra layer of protection to your PYC Member Portal account using an authenticator app.
                    @endif
                </p>
            </div>

            @if($twoFactorConfirmed)
                <span class="px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold">
                    Confirmed &amp; Active
                </span>
            @elseif($twoFactorEnabled)
                <span class="px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold">
                    Awaiting Code Confirmation
                </span>
            @else
                <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-bold">
                    Not Enabled
                </span>
            @endif
        </div>

        @if(! $twoFactorEnabled)
            <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                @csrf
                <button type="submit" class="btn-pyc-primary">
                    Enable Two-Factor Authentication
                </button>
            </form>
        @else
            @if(! $twoFactorConfirmed)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                        <p class="text-sm font-bold text-slate-900">Step 1: Scan QR Code with your Authenticator App</p>
                        <div class="inline-block p-3 bg-white rounded-xl border border-slate-200">
                            {!! $user->twoFactorQrCodeSvg() !!}
                        </div>
                        <p class="text-xs text-slate-600">
                            Manual setup key:
                            <code class="font-mono font-bold text-[#0F2D59] select-all">{{ decrypt($user->two_factor_secret) }}</code>
                        </p>
                    </div>

                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-4">
                        <p class="text-sm font-bold text-slate-900">Step 2: Enter 6-Digit Code to Confirm</p>
                        <form method="POST" action="{{ url('/user/confirmed-two-factor-authentication') }}" class="space-y-3">
                            @csrf
                            <div>
                                <label for="code" class="block text-xs font-semibold text-slate-700 mb-1">6-digit verification code</label>
                                <input id="code" name="code" type="text" inputmode="numeric" required autocomplete="one-time-code"
                                       placeholder="123456"
                                       class="pyc-input bg-white font-mono tracking-widest">
                            </div>
                            <button type="submit" class="btn-pyc-primary">
                                Confirm &amp; Activate 2FA
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-bold text-slate-900">Emergency Recovery Codes</p>
                        <form method="POST" action="{{ url('/user/two-factor-recovery-codes') }}">
                            @csrf
                            <button type="submit" class="text-xs font-bold text-[#1E549F] hover:underline cursor-pointer">
                                Regenerate Recovery Codes
                            </button>
                        </form>
                    </div>
                    <p class="text-xs text-slate-500">Store these single-use recovery codes in a safe password manager in case you lose access to your authenticator device.</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1">
                        @foreach ((array) json_decode(decrypt($user->two_factor_recovery_codes), true) as $recoveryCode)
                            <code class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-800 text-center">
                                {{ $recoveryCode }}
                            </code>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ url('/user/two-factor-authentication') }}" class="pt-2">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-2 text-xs font-bold text-rose-700 hover:bg-rose-100 cursor-pointer">
                    Disable Two-Factor Authentication
                </button>
            </form>
        @endif
    </div>

    <!-- Profile & Password Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="pyc-card p-6 sm:p-7">
            <h2 class="text-base font-extrabold text-slate-900 mb-4">Profile Details</h2>
            <form method="POST" action="{{ route('user-profile-information.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="pyc-input">
                </div>
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="pyc-input">
                </div>
                <button type="submit" class="btn-pyc-primary">
                    Save Profile
                </button>
            </form>
        </div>

        <div class="pyc-card p-6 sm:p-7">
            <h2 class="text-base font-extrabold text-slate-900 mb-4">Change Password</h2>
            <form method="POST" action="{{ route('user-password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="current_password" class="block text-xs font-semibold text-slate-700 mb-1">Current Password</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="pyc-input">
                </div>
                <div>
                    <label for="new_password" class="block text-xs font-semibold text-slate-700 mb-1">New Password (min 10 chars)</label>
                    <input id="new_password" name="password" type="password" required autocomplete="new-password" class="pyc-input">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Confirm New Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="pyc-input">
                </div>
                <button type="submit" class="btn-pyc-primary">
                    Update Password
                </button>
            </form>
        </div>
    </div>
@endcomponent
