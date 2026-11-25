@component('layouts.guest', ['title' => 'Sign In'])
    <div class="text-center mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Welcome Back!</h1>
        <p class="mt-1 text-sm text-slate-500">Sign in to your account</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Input with Envelope Icon -->
        <div class="relative">
            <label for="email" class="sr-only">Email Address</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                </svg>
            </div>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   placeholder="Email Address"
                   class="pyc-input pl-10 py-3">
        </div>

        <!-- Password Input with Lock Icon & Show/Hide Toggle -->
        <div class="relative">
            <label for="password" class="sr-only">Password</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                </svg>
            </div>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   placeholder="Password"
                   class="pyc-input pl-10 pr-10 py-3">
            <button type="button"
                    onclick="const p = document.getElementById('password'); p.type = p.type === 'password' ? 'text' : 'password';"
                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 cursor-pointer"
                    aria-label="Toggle password visibility">
                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                </svg>
            </button>
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between pt-0.5 text-xs">
            <label for="remember" class="inline-flex items-center gap-2 text-slate-600 font-medium cursor-pointer">
                <input id="remember" name="remember" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#0F2D59] focus:ring-[#0F2D59]/30">
                <span>Remember Me</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="font-semibold text-[#1E549F] hover:text-[#0F2D59] hover:underline">
                    Forgot Password?
                </a>
            @endif
        </div>

        <!-- Sign In Button -->
        <button type="submit" class="btn-pyc-primary w-full py-3 text-sm shadow-sm">
            Sign In
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center space-y-2">
        <p class="text-xs text-slate-500">
            Don't have an account?
            <a href="{{ route('home') }}#membership" class="font-semibold text-[#0F2D59] underline hover:text-[#1E549F]">
                Contact the Parish Office
            </a>
        </p>
        <p class="text-[11px] text-slate-400 leading-relaxed">
            Approved PYC members receive a secure single-use invitation link via SMS/email from the General Secretary.
        </p>
    </div>
@endcomponent
