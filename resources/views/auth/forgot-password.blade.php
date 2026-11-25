@component('layouts.guest', ['title' => 'Reset Password'])
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-slate-900">Reset your password</h2>
        <p class="mt-1 text-sm text-slate-500">
            Enter your registered email address and we will send you a secure password reset link.
        </p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20">
        </div>

        <button type="submit"
                class="w-full rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:opacity-95 cursor-pointer"
                style="background-color: var(--pyc-primary);">
            Email Password Reset Link
        </button>

        <div class="text-center">
            <a href="{{ route('login') }}" class="text-xs font-medium text-teal-700 hover:text-teal-800">
                &larr; Return to sign in
            </a>
        </div>
    </form>
@endcomponent
