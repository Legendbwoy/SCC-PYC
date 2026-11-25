@component('layouts.guest', ['title' => 'Sign In'])
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-slate-900">Sign in to your account</h2>
        <p class="mt-1 text-sm text-slate-500">
            Access the Member Portal or Executive Council Portal.
        </p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20">
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-medium text-teal-700 hover:text-teal-800">
                        Forgot password?
                    </a>
                @endif
            </div>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20">
        </div>

        <div class="flex items-center">
            <input id="remember" name="remember" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600">
            <label for="remember" class="ml-2 block text-sm text-slate-600">Keep me signed in on this device</label>
        </div>

        <button type="submit"
                class="w-full rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:opacity-95 cursor-pointer"
                style="background-color: var(--pyc-primary);">
            Sign In Securely
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-100 text-xs text-slate-500 leading-relaxed">
        <strong class="font-medium text-slate-700">New PYC member?</strong>
        Member portal accounts are activated exclusively via secure email/SMS invitation after your Google Form registration is approved by the General Secretary.
    </div>
@endcomponent
