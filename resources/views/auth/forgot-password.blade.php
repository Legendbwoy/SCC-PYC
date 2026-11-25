@component('layouts.guest', ['title' => 'Reset Password'])
    <div class="text-center mb-6">
        <h1 class="text-xl font-extrabold text-slate-900">Forgot Password?</h1>
        <p class="mt-1 text-xs text-slate-500 leading-relaxed">
            Enter your registered email address and we'll send you a secure password reset link.
        </p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus placeholder="you@stceciliapyc.org"
                   class="pyc-input">
        </div>

        <button type="submit" class="btn-pyc-primary w-full py-3">
            Send Password Reset Link
        </button>

        <div class="text-center pt-1">
            <a href="{{ route('login') }}" class="text-xs font-semibold text-[#1E549F] hover:text-[#0F2D59]">
                &larr; Back to Sign In
            </a>
        </div>
    </form>
@endcomponent
