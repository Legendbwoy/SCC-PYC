@component('layouts.guest', ['title' => 'Two-Factor Verification'])
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-slate-900">Two-Factor Authentication</h2>
        <p class="mt-1 text-sm text-slate-500">
            Enter the 6-digit verification code from your authenticator app, or use one of your emergency recovery codes.
        </p>
    </div>

    <form method="POST" action="{{ url('/two-factor-challenge') }}" class="space-y-5">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium text-slate-700">Authenticator 6-digit code</label>
            <input id="code" name="code" type="text" inputmode="numeric" autofocus autocomplete="one-time-code"
                   placeholder="123456"
                   class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm tracking-widest font-mono text-slate-900 shadow-xs focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20">
        </div>

        <div class="relative flex py-1 items-center">
            <div class="grow border-t border-slate-200"></div>
            <span class="shrink mx-3 text-xs uppercase text-slate-400">or recovery code</span>
            <div class="grow border-t border-slate-200"></div>
        </div>

        <div>
            <label for="recovery_code" class="block text-sm font-medium text-slate-700">Emergency recovery code</label>
            <input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code"
                   placeholder="xxxx-xxxx"
                   class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-mono text-slate-900 shadow-xs focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20">
        </div>

        <button type="submit"
                class="w-full rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:opacity-95 cursor-pointer"
                style="background-color: var(--pyc-primary);">
            Verify &amp; Continue
        </button>
    </form>
@endcomponent
