@component('layouts.guest', ['title' => 'Confirm Password'])
    <div class="text-center mb-6">
        <h1 class="text-xl font-extrabold text-slate-900">Confirm Password</h1>
        <p class="mt-1 text-xs text-slate-500">
            Please confirm your current password before continuing this security-sensitive action.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div>
            <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Current Password</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="current-password" class="pyc-input">
        </div>

        <button type="submit" class="btn-pyc-primary w-full py-3">
            Confirm Password
        </button>
    </form>
@endcomponent
