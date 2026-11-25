@component('layouts.guest', ['title' => 'Activate Member Account'])
    <div class="text-center mb-6">
        <span class="inline-block px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold mb-2">
            Approved Member: {{ $member->member_number }}
        </span>
        <h1 class="text-xl font-extrabold text-slate-900">Welcome, {{ $member->fullName() }}!</h1>
        <p class="mt-1 text-xs text-slate-500">
            Set a strong password to activate your St. Cecilia PYC Member Portal account.
        </p>
    </div>

    <form method="POST" action="{{ route('invitations.accept', ['token' => $token]) }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Portal Sign-In Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $member->email) }}" required class="pyc-input">
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Create Password (min 10 chars)</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="pyc-input">
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Confirm Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="pyc-input">
        </div>

        <label class="flex items-start gap-2.5 pt-1 text-xs text-slate-600 cursor-pointer">
            <input type="checkbox" name="optional_consent" value="1" checked class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#0F2D59]">
            <span>I consent to receive optional Parish Youth Council event announcements and monthly dues reminders via SMS and email.</span>
        </label>

        <button type="submit" class="btn-pyc-primary w-full py-3">
            Activate My Portal Account
        </button>
    </form>
@endcomponent
