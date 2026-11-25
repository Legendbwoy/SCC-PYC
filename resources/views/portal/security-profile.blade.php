@component('layouts.app', [
    'title' => 'Security & 2FA',
    'heading' => 'Account Security & Two-Factor Authentication',
    'subheading' => 'Manage your profile information, strong password, and TOTP authenticator security.',
])
    <!-- Two-Factor Authentication Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Two-Factor Authentication (TOTP)</h2>
                <p class="mt-1 text-sm text-slate-600">
                    @if($user->requiresTwoFactor())
                        Mandatory for all PYC executive and administrative officers (Google Authenticator, Microsoft Authenticator, Authy, or 1Password).
                    @else
                        Add an extra layer of protection to your PYC Member Portal account using an authenticator app.
                    @endif
                </p>
            </div>

            @if($twoFactorConfirmed)
                <span class="px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
                    Confirmed &amp; Active
                </span>
            @elseif($twoFactorEnabled)
                <span class="px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold">
                    Awaiting Code Confirmation
                </span>
            @else
                <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                    Not Enabled
                </span>
            @endif
        </div>

        @if(! $twoFactorEnabled)
            <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                @csrf
                <button type="submit"
                        class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-xs cursor-pointer"
                        style="background-color: var(--pyc-primary);">
                    Enable Two-Factor Authentication
                </button>
            </form>
        @else
            @if(! $twoFactorConfirmed)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                    <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <p class="text-sm font-semibold text-slate-900">Step 1: Scan QR Code with your Authenticator App</p>
                        <div class="inline-block p-3 bg-white rounded-xl border border-slate-200">
                            {!! $user->twoFactorQrCodeSvg() !!}
                        </div>
                        <p class="text-xs text-slate-600">
                            Manual setup key:
                            <code class="font-mono font-semibold text-slate-900 select-all">{{ decrypt($user->two_factor_secret) }}</code>
                        </p>
                    </div>

                    <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-4">
                        <p class="text-sm font-semibold text-slate-900">Step 2: Enter 6-Digit Code to Confirm</p>
                        <form method="POST" action="{{ url('/user/confirmed-two-factor-authentication') }}" class="space-y-3">
                            @csrf
                            <div>
                                <label for="code" class="block text-xs font-medium text-slate-700">6-digit verification code</label>
                                <input id="code" name="code" type="text" inputmode="numeric" required autocomplete="one-time-code"
                                       placeholder="123456"
                                       class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-mono tracking-widest">
                            </div>
                            <button type="submit"
                                    class="rounded-xl px-4 py-2 text-sm font-semibold text-white cursor-pointer"
                                    style="background-color: var(--pyc-primary);">
                                Confirm &amp; Activate 2FA
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-slate-900">Emergency Recovery Codes</p>
                        <form method="POST" action="{{ url('/user/two-factor-recovery-codes') }}">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-teal-700 hover:text-teal-800 cursor-pointer">
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
                <button type="submit" class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100 cursor-pointer">
                    Disable Two-Factor Authentication
                </button>
            </form>
        @endif
    </div>

    <!-- Profile & Password Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <h2 class="text-base font-bold text-slate-900 mb-4">Profile Details</h2>
            <form method="POST" action="{{ route('user-profile-information.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Full Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email Address</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 cursor-pointer">
                    Save Profile
                </button>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <h2 class="text-base font-bold text-slate-900 mb-4">Change Password</h2>
            <form method="POST" action="{{ route('user-password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="current_password" class="block text-sm font-medium text-slate-700">Current Password</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label for="new_password" class="block text-sm font-medium text-slate-700">New Password (min 10 chars)</label>
                    <input id="new_password" name="password" type="password" required autocomplete="new-password"
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm New Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 cursor-pointer">
                    Update Password
                </button>
            </form>
        </div>
    </div>
@endcomponent
