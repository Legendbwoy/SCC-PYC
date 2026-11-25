@component('layouts.guest', ['title' => 'Invitation Invalid or Expired'])
    <div class="text-center space-y-4">
        <div class="mx-auto h-12 w-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center font-extrabold text-lg">!</div>
        <h1 class="text-xl font-extrabold text-slate-900">Invitation Link Unavailable</h1>
        <p class="text-xs text-slate-600 leading-relaxed">
            This member portal invitation link is invalid, has expired, or has already been used to activate an account.
        </p>
        <p class="text-xs text-slate-500">
            Please contact the PYC General Secretary for a new single-use invitation link, or sign in below if your account is already active.
        </p>
        <a href="{{ route('login') }}" class="btn-pyc-primary w-full py-3">
            Go to Sign In
        </a>
    </div>
@endcomponent
