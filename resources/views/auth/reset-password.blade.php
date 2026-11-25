@component('layouts.guest', ['title' => 'Set New Password'])
    <div class="text-center mb-6">
        <h1 class="text-xl font-extrabold text-slate-900">Choose a New Password</h1>
        <p class="mt-1 text-xs text-slate-500">
            Use at least 10 characters including uppercase, lowercase, and numbers.
        </p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required class="pyc-input">
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">New Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="pyc-input">
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Confirm New Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="pyc-input">
        </div>

        <button type="submit" class="btn-pyc-primary w-full py-3">
            Reset Password
        </button>
    </form>
@endcomponent
