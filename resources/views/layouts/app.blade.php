<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Portal' }} — {{ $pycBranding['short_name'] ?? 'SCC-PYC' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        :root {
            --pyc-primary: {{ $pycBranding['primary_color'] ?? '#0f766e' }};
            --pyc-accent: {{ $pycBranding['accent_color'] ?? '#b45309' }};
        }
    </style>
</head>
<body class="min-h-full font-sans antialiased text-slate-900">
@php
    /** @var \App\Models\User $authUser */
    $authUser = auth()->user();
    $isExecutive = $authUser->isExecutiveOfficer();
@endphp

<div class="min-h-screen flex flex-col lg:flex-row">
    <!-- Sidebar -->
    <aside class="w-full lg:w-72 bg-slate-900 text-slate-200 flex flex-col shrink-0">
        <div class="px-6 py-5 border-b border-slate-800 flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0" style="background-color: var(--pyc-primary);">
                PYC
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-teal-400 truncate">
                    {{ $pycBranding['parish_name'] ?? 'St. Cecilia Catholic Church' }}
                </p>
                <p class="text-sm font-bold text-white truncate">
                    {{ $isExecutive ? 'Executive Portal' : 'Member Portal' }}
                </p>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto text-sm">
            <div>
                <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Overview</p>
                <a href="{{ route('dashboard') }}"
                   class="flex items-center justify-between px-3 py-2 rounded-xl font-medium transition {{ request()->routeIs('dashboard') ? 'bg-teal-600/20 text-teal-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <span>Dashboard</span>
                </a>
            </div>

            @if($isExecutive)
                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Council Operations</p>
                    <div class="space-y-1">
                        @can('members.view')
                            <a href="{{ route('modules.overview', 'membership') }}"
                               class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ request()->is('modules/membership') ? 'bg-teal-600/20 text-teal-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span>Membership &amp; Sync</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">S2</span>
                            </a>
                        @endcan
                        @can('dues.view')
                            <a href="{{ route('modules.overview', 'contributions') }}"
                               class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ request()->is('modules/contributions') ? 'bg-teal-600/20 text-teal-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span>Dues &amp; Campaigns</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">S4</span>
                            </a>
                        @endcan
                        @can('payments.view')
                            <a href="{{ route('modules.overview', 'payments') }}"
                               class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ request()->is('modules/payments') ? 'bg-teal-600/20 text-teal-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span>Payments &amp; Receipts</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">S5</span>
                            </a>
                        @endcan
                        @can('accounting.view')
                            <a href="{{ route('modules.overview', 'accounting') }}"
                               class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ request()->is('modules/accounting') ? 'bg-teal-600/20 text-teal-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span>Accounting Ledger</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">S6</span>
                            </a>
                        @endcan
                        @can('expenses.view')
                            <a href="{{ route('modules.overview', 'expenses') }}"
                               class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ request()->is('modules/expenses') ? 'bg-teal-600/20 text-teal-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span>Expenses</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">S6</span>
                            </a>
                        @endcan
                        <a href="{{ route('modules.overview', 'events') }}"
                           class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ request()->is('modules/events') ? 'bg-teal-600/20 text-teal-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <span>Events</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">S7</span>
                        </a>
                        @can('notifications.view')
                            <a href="{{ route('communications.index') }}"
                               class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ request()->routeIs('communications.*') ? 'bg-teal-600/20 text-teal-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span>SMS &amp; Email Notices</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-semibold">LIVE</span>
                            </a>
                        @endcan
                        @can('reports.view')
                            <a href="{{ route('modules.overview', 'reports') }}"
                               class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ request()->is('modules/reports') ? 'bg-teal-600/20 text-teal-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span>Reports &amp; Exports</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">S6</span>
                            </a>
                        @endcan
                    </div>
                </div>

                @if($authUser->can('users.manage') || $authUser->can('settings.manage') || $authUser->can('audit_logs.view'))
                    <div>
                        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Administration &amp; Governance</p>
                        <div class="space-y-1">
                            @can('users.manage')
                                <a href="{{ route('admin.users.index') }}"
                                   class="flex items-center px-3 py-2 rounded-xl transition {{ request()->routeIs('admin.users.*') ? 'bg-teal-600/20 text-teal-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    Users &amp; Roles
                                </a>
                            @endcan
                            @can('settings.manage')
                                <a href="{{ route('admin.settings.index') }}"
                                   class="flex items-center px-3 py-2 rounded-xl transition {{ request()->routeIs('admin.settings.*') ? 'bg-teal-600/20 text-teal-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    Organization &amp; Branding
                                </a>
                            @endcan
                            @can('audit_logs.view')
                                <a href="{{ route('admin.audit-logs.index') }}"
                                   class="flex items-center px-3 py-2 rounded-xl transition {{ request()->routeIs('admin.audit-logs.*') ? 'bg-teal-600/20 text-teal-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    Audit Trail
                                </a>
                            @endcan
                        </div>
                    </div>
                @endif
            @endif

            <div>
                <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Account</p>
                <div class="space-y-1">
                    <a href="{{ route('profile.notifications') }}"
                       class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ request()->routeIs('profile.notifications*') ? 'bg-teal-600/20 text-teal-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span>Alerts &amp; Consent</span>
                    </a>
                    <a href="{{ route('profile.security') }}"
                       class="flex items-center justify-between px-3 py-2 rounded-xl transition {{ request()->routeIs('profile.security') ? 'bg-teal-600/20 text-teal-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span>Security &amp; 2FA</span>
                        @if($authUser->hasConfirmedTwoFactorAuthentication())
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-semibold">2FA ON</span>
                        @elseif($authUser->requiresTwoFactor())
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 font-semibold">REQUIRED</span>
                        @endif
                    </a>
                </div>
            </div>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/50">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ $authUser->name }}</p>
                    <p class="text-xs text-teal-400 truncate">{{ $authUser->primaryRoleName() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-medium text-slate-200 cursor-pointer">
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">{{ $heading ?? ($title ?? 'Dashboard') }}</h1>
                @if(!empty($subheading))
                    <p class="text-sm text-slate-500 mt-0.5">{{ $subheading }}</p>
                @endif
            </div>
            <div class="flex items-center gap-3 text-xs text-slate-500">
                <span class="px-2.5 py-1 rounded-full bg-slate-100 font-medium text-slate-700">
                    {{ $pycBranding['currency'] ?? 'GHS' }} &bull; {{ $pycBranding['timezone'] ?? 'Africa/Accra' }}
                </span>
                <span class="hidden sm:inline">{{ now()->timezone($pycBranding['timezone'] ?? 'Africa/Accra')->format('d M Y, H:i') }}</span>
            </div>
        </header>

        <main class="flex-1 p-6 max-w-7xl w-full mx-auto space-y-6">
            @if (session('status'))
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                    @if (session('status') === 'two-factor-authentication-enabled')
                        Two-factor authentication secret generated. Scan the QR code below and enter a 6-digit code to confirm activation.
                    @elseif (session('status') === 'two-factor-authentication-confirmed')
                        Two-factor authentication is now confirmed and active on your account.
                    @elseif (session('status') === 'two-factor-authentication-disabled')
                        Two-factor authentication has been disabled.
                    @elseif (session('status') === 'recovery-codes-generated')
                        New emergency recovery codes have been generated. Store them safely offline.
                    @elseif (session('status') === 'password-updated')
                        Your password has been updated.
                    @elseif (session('status') === 'profile-information-updated')
                        Your profile details have been updated.
                    @else
                        {{ session('status') }}
                    @endif
                </div>
            @endif

            @if (session('warning'))
                <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900 font-medium">
                    {{ session('warning') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>

@livewireScripts
</body>
</html>
