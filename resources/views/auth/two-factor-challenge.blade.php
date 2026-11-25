@component('layouts.guest', ['title' => 'Two-Factor Verification'])
    <div class="text-center mb-6">
        <h1 class="text-xl font-extrabold text-slate-900">Two-Factor Authentication</h1>
        <p class="mt-1 text-xs text-slate-500">
            Enter the 6-digit verification code from your authenticator app or an emergency recovery code.
        </p>
    </div>

    <form method="POST" action="{{ url('/two-factor-challenge') }}" class="space-y-4">
        @csrf

        <div>
            <label for="code" class="block text-xs font-semibold text-slate-700 mb-1">Authenticator 6-Digit Code</label>
            <input id="code" name="code" type="text" inputmode="numeric" autofocus autocomplete="one-time-code"
                   placeholder="123456"
                   class="pyc-input text-center tracking-[0.35em] font-mono text-base">
        </div>

        <div class="relative flex py-1 items-center">
            <div class="grow border-t border-slate-200"></div>
            <span class="shrink mx-3 text-[11px] uppercase font-semibold text-slate-400">or recovery code</span>
            <div class="grow border-t border-slate-200"></div>
        </div>

        <div>
            <label for="recovery_code" class="block text-xs font-semibold text-slate-700 mb-1">Emergency Recovery Code</label>
            <input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code"
                   placeholder="xxxx-xxxx"
                   class="pyc-input font-mono text-center">
        </div>

        <button type="submit" class="btn-pyc-primary w-full py-3">
            Verify &amp; Continue
        </button>
    </form>
@endcomponent
