@component('layouts.guest', ['title' => 'Set New Password'])
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-slate-900">Choose a new password</h2>
        <p class="mt-1 text-sm text-slate-500">
            Use at least 10 characters including uppercase, lowercase, and numbers.
        </p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required
                   class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">New password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20">
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20">
        </div>

        <button type="submit"
                class="w-full rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:opacity-95 cursor-pointer"
                style="background-color: var(--pyc-primary);">
            Reset Password
        </button>
    </form>
@endcomponent
