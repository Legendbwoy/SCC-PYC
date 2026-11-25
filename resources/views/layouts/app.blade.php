<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F3F6FB]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Portal' }} — {{ $pycBranding['short_name'] ?? 'SCC-PYC' }}</title>
    @if(!empty($pycBranding['favicon_url']))
        <link rel="icon" href="{{ $pycBranding['favicon_url'] }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        :root {
            --pyc-primary: {{ $pycBranding['primary_color'] ?? '#0F2D59' }};
            --pyc-accent: {{ $pycBranding['accent_color'] ?? '#F5C542' }};
        }
    </style>
</head>
<body class="min-h-full font-sans antialiased text-slate-900 bg-[#F3F6FB] pb-16 lg:pb-0">
@php
    /** @var \App\Models\User $authUser */
    $authUser = auth()->user();
    $isExecutive = $authUser->isExecutiveOfficer();
    $roleLabel = str_replace('PYC ', '', $authUser->primaryRoleName());
    $nameParts = preg_split('/\s+/', trim((string) $authUser->name)) ?: ['P'];
    $initials = strtoupper(substr($nameParts[0] ?? 'P', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
    $navActive = 'bg-[#1D56A6] text-white shadow-xs font-semibold';
    $navIdle = 'text-sky-100/80 hover:bg-white/10 hover:text-white font-medium';
@endphp

<div class="min-h-screen flex">
    <!-- Mobile Sidebar Backdrop -->
    <div id="pycSidebarBackdrop"
         onclick="document.getElementById('pycSidebar').classList.add('-translate-x-full'); this.classList.add('hidden');"
         class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-xs hidden lg:hidden"></div>

    <!-- Left Royal Navy Sidebar -->
    <aside id="pycSidebar"
           class="fixed inset-y-0 left-0 z-50 w-64 -translate-x-full lg:translate-x-0 lg:static lg:w-64 bg-gradient-to-b from-[#081F3F] via-[#0B2750] to-[#081B38] text-white flex flex-col shrink-0 transition-transform duration-200 ease-out shadow-xl lg:shadow-none">

        <!-- PYC Crest Header -->
        <div class="px-5 py-5 border-b border-white/10 flex items-center justify-between gap-2">
            <a href="{{ route('dashboard') }}" class="focus:outline-none">
                <x-pyc-logo variant="dark" subtitle="Parish Youth Council" size="sm" />
            </a>
            <button type="button"
                    onclick="document.getElementById('pycSidebar').classList.add('-translate-x-full'); document.getElementById('pycSidebarBackdrop').classList.add('hidden');"
                    class="lg:hidden rounded-lg p-1.5 text-sky-200 hover:bg-white/10"
                    aria-label="Close navigation">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-3.5 py-5 space-y-1.5 overflow-y-auto text-sm">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('dashboard') ? $navActive : $navIdle }}">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                <span>Dashboard</span>
            </a>

            @if(!$isExecutive)
                <a href="{{ route('member.profile') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('member.profile*') ? $navActive : $navIdle }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                    <span>My Profile</span>
                </a>

                <a href="{{ route('member.dues') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('member.dues*') ? $navActive : $navIdle }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/></svg>
                    <span>My Dues</span>
                </a>

                <a href="{{ route('member.events') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('member.events*') ? $navActive : $navIdle }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    <span>Events</span>
                </a>

                <a href="{{ route('member.payments') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('member.payments*') ? $navActive : $navIdle }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                    <span>My Payments</span>
                </a>
            @else
                @can('members.view')
                    <a href="{{ route('membership.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('membership.*') ? $navActive : $navIdle }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                        <span>Membership</span>
                    </a>
                @endcan

                <a href="{{ route('events.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('events.*') ? $navActive : $navIdle }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    <span>Events</span>
                </a>

                <a href="{{ route('admin.media.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.media.*') ? $navActive : $navIdle }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    <span>Media &amp; Gallery</span>
                </a>

                @can('payments.view')
                    <a href="{{ route('payments.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('payments.*') ? $navActive : $navIdle }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                        <span>Payments</span>
                    </a>
                @endcan

                @can('dues.view')
                    <a href="{{ route('contributions.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('contributions.*') ? $navActive : $navIdle }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                        <span>Dues &amp; Campaigns</span>
                    </a>
                @endcan

                @can('accounting.view')
                    <a href="{{ route('accounting.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('accounting.*') ? $navActive : $navIdle }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                        <span>Accounting Ledger</span>
                    </a>
                @endcan

                @can('expenses.view')
                    <a href="{{ route('expenses.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('expenses.*') ? $navActive : $navIdle }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185Z"/></svg>
                        <span>Expenses</span>
                    </a>
                @endcan

                @can('reports.view')
                    <a href="{{ route('reports.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('reports.*') ? $navActive : $navIdle }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                        <span>Reports</span>
                    </a>
                @endcan

                @can('notifications.view')
                    <a href="{{ route('communications.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('communications.*') ? $navActive : $navIdle }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46"/></svg>
                        <span>Announcements</span>
                    </a>
                @endcan
            @endif

            <!-- Notifications & Preferences -->
            <a href="{{ route('profile.notifications') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('profile.notifications*') ? $navActive : $navIdle }}">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                <span>Notifications</span>
            </a>

            <!-- Settings / Security -->
            <a href="{{ route('profile.security') }}"
               class="flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('profile.security') ? $navActive : $navIdle }}">
                <span class="flex items-center gap-3">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                    <span>Settings</span>
                </span>
                @if($authUser->hasConfirmedTwoFactorAuthentication())
                    <span class="text-[10px] px-1.5 py-0.5 rounded-md bg-emerald-400/20 text-emerald-300 font-bold">2FA</span>
                @elseif($authUser->requiresTwoFactor())
                    <span class="text-[10px] px-1.5 py-0.5 rounded-md bg-amber-400/20 text-amber-200 font-bold">SETUP</span>
                @endif
            </a>

            @if($isExecutive && ($authUser->can('users.manage') || $authUser->can('settings.manage') || $authUser->can('audit_logs.view')))
                <div class="pt-4 mt-4 border-t border-white/10 space-y-1">
                    <p class="px-3.5 pb-1 text-[10px] font-bold uppercase tracking-wider text-sky-300/60">Governance</p>
                    @can('users.manage')
                        <a href="{{ route('admin.users.index') }}"
                           class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition {{ request()->routeIs('admin.users.*') ? $navActive : $navIdle }}">
                            <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            <span>Users &amp; Roles</span>
                        </a>
                    @endcan
                    @can('settings.manage')
                        <a href="{{ route('admin.settings.index') }}"
                           class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition {{ request()->routeIs('admin.settings.*') ? $navActive : $navIdle }}">
                            <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z"/></svg>
                            <span>Parish Branding</span>
                        </a>
                    @endcan
                    @can('audit_logs.view')
                        <a href="{{ route('admin.audit-logs.index') }}"
                           class="flex items-center gap-3 px-3.5 py-2 rounded-xl transition {{ request()->routeIs('admin.audit-logs.*') ? $navActive : $navIdle }}">
                            <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                            <span>Audit Logs</span>
                        </a>
                    @endcan
                </div>
            @endif
        </nav>

        <!-- Bottom Coral-Red Logout Button (Matching Reference Mockup) -->
        <div class="p-4 border-t border-white/10">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-rose-400 hover:bg-rose-500/15 hover:text-rose-300 transition cursor-pointer">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Right Workspace -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Clean White Top Bar with Profile Dropdown -->
        <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-4 sm:px-8 py-3.5 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button"
                        onclick="document.getElementById('pycSidebar').classList.remove('-translate-x-full'); document.getElementById('pycSidebarBackdrop').classList.remove('hidden');"
                        class="lg:hidden rounded-xl border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 cursor-pointer"
                        aria-label="Open menu">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                </button>
                <a href="{{ route('home') }}" class="hidden sm:inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-[#0F2D59] transition">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>{{ $pycBranding['parish_name'] ?? 'St. Cecilia Catholic Church' }}</span>
                </a>
            </div>

            <!-- Right User Profile & Quick Actions -->
            <div class="flex items-center gap-3">
                <a href="{{ route('profile.notifications') }}"
                   class="relative rounded-xl p-2 text-slate-500 hover:bg-slate-100 hover:text-[#0F2D59] transition"
                   title="Notifications & Preferences">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                    </svg>
                </a>

                <details class="relative group">
                    <summary class="list-none flex items-center gap-3 rounded-2xl px-2.5 py-1.5 hover:bg-slate-100/80 transition cursor-pointer select-none">
                        @if($authUser->avatarUrl())
                            <img src="{{ $authUser->avatarUrl() }}" alt="{{ $authUser->name }}" class="h-9 w-9 rounded-full object-cover ring-2 ring-[#0F2D59]/15 shrink-0">
                        @else
                            <div class="h-9 w-9 rounded-full bg-gradient-to-br from-[#0F2D59] to-[#1E549F] text-white font-bold text-xs flex items-center justify-center shadow-xs shrink-0">
                                {{ $initials }}
                            </div>
                        @endif
                        <div class="hidden sm:block text-left leading-tight">
                            <p class="text-sm font-bold text-slate-900">{{ $authUser->name }}</p>
                            <p class="text-xs text-slate-500 flex items-center gap-1">
                                <span>{{ $roleLabel }}</span>
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                            </p>
                        </div>
                    </summary>

                    <div class="absolute right-0 mt-2 w-56 rounded-2xl bg-white py-2 shadow-xl ring-1 ring-slate-200 z-50 text-xs">
                        <div class="px-4 py-2 border-b border-slate-100 sm:hidden">
                            <p class="font-bold text-slate-900 truncate">{{ $authUser->name }}</p>
                            <p class="text-slate-500">{{ $roleLabel }}</p>
                        </div>
                        @if(!$isExecutive)
                            <a href="{{ route('member.profile') }}" class="block px-4 py-2 font-medium text-slate-700 hover:bg-slate-50">My Member Profile</a>
                        @endif
                        <a href="{{ route('profile.notifications') }}" class="block px-4 py-2 font-medium text-slate-700 hover:bg-slate-50">Notification Preferences</a>
                        <a href="{{ route('profile.security') }}" class="block px-4 py-2 font-medium text-slate-700 hover:bg-slate-50">Security &amp; 2FA</a>
                        <a href="{{ route('home') }}" class="block px-4 py-2 font-medium text-slate-700 hover:bg-slate-50">Public PYC Website</a>
                        <div class="my-1 border-t border-slate-100"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 font-semibold text-rose-600 hover:bg-rose-50 cursor-pointer">
                                Sign Out
                            </button>
                        </form>
                    </div>
                </details>
            </div>
        </header>

        <!-- Page Workspace Canvas -->
        <main class="flex-1 px-4 sm:px-8 py-6 sm:py-8 max-w-7xl w-full mx-auto space-y-6">
            <!-- Prominent Page Title & Subtitle (Matching Reference Mockups) -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-[1.75rem] font-extrabold tracking-tight text-slate-900">
                        {{ $heading ?? ($title ?? 'Dashboard') }}
                    </h1>
                    @if(!empty($subheading))
                        <p class="text-sm text-slate-500 mt-1">{{ $subheading }}</p>
                    @endif
                </div>
                @if(!empty($headerActions))
                    <div>{{ $headerActions }}</div>
                @endif
            </div>

            @if (session('status'))
                <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm font-medium text-emerald-800 flex items-center gap-2.5">
                    <span class="h-5 w-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs font-bold shrink-0">&check;</span>
                    <span>
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
                    </span>
                </div>
            @endif

            @if (session('warning'))
                <div class="rounded-2xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900 font-medium">
                    {{ session('warning') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">
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

<!-- Mobile Bottom Navigation Bar -->
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white/95 backdrop-blur-md border-t border-slate-200 px-3 py-2 flex items-center justify-around text-[11px] font-semibold text-slate-500">
    <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('dashboard') ? 'text-[#0F2D59]' : '' }}">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
        <span>Home</span>
    </a>
    <a href="{{ $isExecutive ? route('events.index') : route('member.events') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('*events*') ? 'text-[#0F2D59]' : '' }}">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75"/></svg>
        <span>Events</span>
    </a>
    <a href="{{ $isExecutive ? route('payments.index') : route('member.payments') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('*payments*') || request()->routeIs('member.dues*') ? 'text-[#0F2D59]' : '' }}">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
        <span>Payments</span>
    </a>
    <a href="{{ $isExecutive ? route('profile.security') : route('member.profile') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('member.profile*') || request()->routeIs('profile.*') ? 'text-[#0F2D59]' : '' }}">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
        <span>Profile</span>
    </a>
</nav>

@livewireScripts
</body>
</html>
