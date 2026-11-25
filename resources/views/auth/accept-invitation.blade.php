@component('layouts.guest', ['title' => 'Activate Member Account'])
    <div class="mb-6">
        <span class="inline-block px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 text-xs font-semibold mb-2">
            Approved Member: {{ $member->member_number }}
        </span>
        <h2 class="text-lg font-semibold text-slate-900">Welcome, {{ $member->fullName() }}</h2>
        <p class="mt-1 text-sm text-slate-500">
            Set a strong password below to activate your personal St. Cecilia PYC Member Portal account.
        </p>
    </div>

    <form method="POST" action="{{ route('invitations.accept', ['token' => $token]) }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Portal Sign-In Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $member->email) }}" required
                   class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Create Password (min 10 chars)</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">
        </div>

        <label class="flex items-start gap-2.5 pt-1 text-xs text-slate-600">
            <input type="checkbox" name="optional_consent" value="1" checked class="mt-0.5 rounded border-slate-300 text-teal-700">
            <span>I consent to receive optional Parish Youth Council event announcements and monthly dues reminders via SMS and email (you can change this anytime in your portal).</span>
        </label>

        <button type="submit"
                class="w-full rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-xs cursor-pointer"
                style="background-color: var(--pyc-primary);">
            Activate My Portal Account
        </button>
    </form>
@endcomponent
