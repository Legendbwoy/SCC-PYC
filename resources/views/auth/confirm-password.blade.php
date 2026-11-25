@component('layouts.guest', ['title' => 'Confirm Password'])
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-slate-900">Confirm your password</h2>
        <p class="mt-1 text-sm text-slate-500">
            This is a security-sensitive action. Please confirm your current password before continuing.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Current password</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="current-password"
                   class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20">
        </div>

        <button type="submit"
                class="w-full rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:opacity-95 cursor-pointer"
                style="background-color: var(--pyc-primary);">
            Confirm Password
        </button>
    </form>
@endcomponent
