@component('layouts.guest', ['title' => 'Invitation Invalid or Expired'])
    <div class="text-center space-y-4">
        <div class="mx-auto h-12 w-12 rounded-full bg-rose-50 text-rose-700 flex items-center justify-center font-bold text-lg">!</div>
        <h2 class="text-lg font-semibold text-slate-900">Invitation Link Unavailable</h2>
        <p class="text-sm text-slate-600 leading-relaxed">
            This member portal invitation link is invalid, has expired, or has already been used to activate an account.
        </p>
        <p class="text-xs text-slate-500">
            Please contact the PYC General Secretary to request a fresh single-use invitation link, or sign in below if you already activated your account.
        </p>
        <a href="{{ route('login') }}"
           class="inline-block w-full rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
           style="background-color: var(--pyc-primary);">
            Go to Sign In
        </a>
    </div>
@endcomponent
