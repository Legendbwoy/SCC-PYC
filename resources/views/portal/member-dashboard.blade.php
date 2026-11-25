@php
    $firstName = explode(' ', trim((string) $user->name))[0] ?? $user->name;
    $hasArrears = isset($statement['arrears_due']) && $statement['arrears_due']->isPositive();
    $outstandingFormatted = isset($statement['total_outstanding']) ? $statement['total_outstanding']->format() : 'GHS 0.00';
@endphp

@component('layouts.app', [
    'title' => 'Member Dashboard',
    'heading' => 'Member Dashboard',
    'subheading' => "Welcome back, {$firstName}! · Personal Member Portal",
])
    <!-- 4 Pastel KPI Cards (Matching Reference Member Dashboard) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <!-- 1. Member Status -->
        <a href="{{ route('member.profile') }}" class="pyc-kpi-blue group transition hover:-translate-y-0.5">
            <div class="h-10 w-10 rounded-xl bg-sky-100 text-blue-600 flex items-center justify-center mb-3">
                <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z"/>
                </svg>
            </div>
            <p class="text-xs font-medium text-slate-500">Member Status</p>
            <p class="mt-1 text-xl font-extrabold text-slate-900 capitalize">
                {{ $member?->status ?? 'Active' }}
            </p>
            <p class="mt-1 text-xs font-mono font-semibold text-blue-700">
                {{ $member?->member_number ?? 'Portal Member' }}
            </p>
        </a>

        <!-- 2. Next Event -->
        <a href="{{ route('member.events') }}" class="pyc-kpi-green group transition hover:-translate-y-0.5">
            <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3">
                <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                </svg>
            </div>
            <p class="text-xs font-medium text-slate-500">Next Event</p>
            <p class="mt-1 text-lg font-extrabold text-slate-900 truncate">
                {{ $nextEvent?->title ?? 'Parish Fellowship' }}
            </p>
            <p class="mt-1 text-xs font-semibold text-emerald-700 flex items-center gap-1">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                <span>{{ $nextEvent ? $nextEvent->starts_at->timezone('Africa/Accra')->format('M j, Y') : 'Check Calendar' }}</span>
            </p>
        </a>

        <!-- 3. Dues Status -->
        <a href="{{ route('member.dues') }}" class="pyc-kpi-amber group transition hover:-translate-y-0.5">
            <div class="h-10 w-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center mb-3">
                <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75"/>
                </svg>
            </div>
            <p class="text-xs font-medium text-slate-500">Dues Status</p>
            <p class="mt-1 text-xl font-extrabold {{ $hasArrears ? 'text-amber-800' : 'text-slate-900' }}">
                {{ $hasArrears ? 'Balance Due' : 'Up to Date' }}
            </p>
            <p class="mt-1 text-xs font-semibold {{ $hasArrears ? 'text-amber-700' : 'text-emerald-700' }}">
                {{ $hasArrears ? $outstandingFormatted : '(' . $monthlyDues->format() . ' / mo)' }}
            </p>
        </a>

        <!-- 4. Notifications -->
        <a href="{{ route('profile.notifications') }}" class="pyc-kpi-purple group transition hover:-translate-y-0.5">
            <div class="h-10 w-10 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center mb-3">
                <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                </svg>
            </div>
            <p class="text-xs font-medium text-slate-500">Notifications</p>
            <p class="mt-1 text-xl font-extrabold text-slate-900">
                {{ number_format($unreadNotificationsCount) }} New
            </p>
            <p class="mt-1 text-xs font-semibold text-violet-700">Manage alert channels &rarr;</p>
        </a>
    </div>

    <!-- Main 2-Column Content: Upcoming Events Spotlight + Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Upcoming Events Card -->
        <div class="lg:col-span-7 pyc-card p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-extrabold text-slate-900">Upcoming Events</h2>
                <a href="{{ route('member.events') }}" class="text-xs font-bold text-[#1E549F] hover:underline">
                    View All &rarr;
                </a>
            </div>

            @if($nextEvent)
                @php
                    $myReg = $nextEvent->registrations->first();
                    $isRegistered = $myReg && in_array($myReg->status, ['registered', 'attended'], true);
                @endphp
                <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center gap-5">
                    <x-pyc-event-banner class="h-36 w-full sm:w-36" />
                    <div class="space-y-2 flex-1 min-w-0">
                        <h3 class="text-lg font-extrabold text-slate-900">{{ $nextEvent->title }}</h3>
                        @if($nextEvent->description)
                            <p class="text-xs text-slate-600 line-clamp-2">{{ $nextEvent->description }}</p>
                        @else
                            <p class="text-xs text-slate-600">Spiritual Renewal &amp; Parish Youth Fellowship</p>
                        @endif
                        <div class="space-y-0.5 text-xs text-slate-500 pt-0.5">
                            <p class="font-medium text-slate-700">
                                {{ $nextEvent->starts_at->timezone('Africa/Accra')->format('D, M j, Y • g:i A') }}
                            </p>
                            <p>{{ $nextEvent->venue }}</p>
                        </div>

                        <div class="pt-2">
                            @if($isRegistered)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-4 py-1.5 text-xs font-bold text-emerald-800">
                                    <span>&check;</span> Registered
                                </span>
                            @elseif($nextEvent->allow_registration && $member)
                                <form method="POST" action="{{ route('member.events.register', $nextEvent) }}">
                                    @csrf
                                    <button type="submit" class="btn-pyc-green">
                                        Register Now
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('member.events') }}" class="btn-pyc-green">
                                    View Event
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if($upcomingEvents->count() > 1)
                    <div class="divide-y divide-slate-100 pt-1">
                        @foreach($upcomingEvents->skip(1) as $moreEvent)
                            <div class="py-3 flex items-center justify-between gap-3 text-xs">
                                <div>
                                    <p class="font-bold text-slate-900">{{ $moreEvent->title }}</p>
                                    <p class="text-slate-500">{{ $moreEvent->venue }}</p>
                                </div>
                                <span class="font-semibold text-[#1E549F] shrink-0">
                                    {{ $moreEvent->starts_at->timezone('Africa/Accra')->format('M j') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-5 flex flex-col sm:flex-row items-center gap-5">
                    <x-pyc-event-banner class="h-32 w-32" />
                    <div class="space-y-1.5 text-center sm:text-left">
                        <h3 class="text-base font-bold text-slate-900">No Upcoming Events Published Yet</h3>
                        <p class="text-xs text-slate-500">
                            Upcoming St. Cecilia Parish Youth Council retreats, Bible studies, and general meetings will appear here as soon as they are announced.
                        </p>
                        <a href="{{ route('member.events') }}" class="inline-block pt-1 text-xs font-bold text-[#1E549F] hover:underline">
                            Browse Events Page &rarr;
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <!-- Recent Activity Card -->
        <div class="lg:col-span-5 pyc-card p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-extrabold text-slate-900">Recent Activity</h2>
                <a href="{{ route('member.payments') }}" class="text-xs font-bold text-[#1E549F] hover:underline">
                    View All &rarr;
                </a>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($recentPayments as $pay)
                    <div class="py-3.5 flex items-start gap-3.5">
                        <div class="h-9 w-9 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-bold text-slate-900">Payment Receipt</p>
                                @if($pay->receipt)
                                    <a href="{{ route('payments.receipt.pdf', $pay) }}" class="text-[11px] font-mono font-bold text-[#1E549F] hover:underline">
                                        PDF
                                    </a>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500">
                                {{ $pay->amountMoney()->format() }} &bull; {{ $pay->created_at?->timezone('Africa/Accra')->format('M j, Y') }}
                            </p>
                        </div>
                    </div>
                @endforeach

                @foreach($recentRegistrations as $reg)
                    <div class="py-3.5 flex items-start gap-3.5">
                        <div class="h-9 w-9 rounded-full bg-violet-100 text-violet-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-slate-900">Event Registration</p>
                            <p class="text-xs text-slate-500 truncate">
                                {{ $reg->event?->title ?? 'PYC Event' }} &bull; {{ $reg->created_at?->timezone('Africa/Accra')->format('M j, Y') }}
                            </p>
                        </div>
                    </div>
                @endforeach

                @if($member)
                    <div class="py-3.5 flex items-start gap-3.5">
                        <div class="h-9 w-9 rounded-full bg-sky-100 text-blue-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-slate-900">Official PYC Membership</p>
                            <p class="text-xs text-slate-500">
                                {{ $member->member_number }} &bull; Joined {{ $member->joined_at?->format('M j, Y') ?? 'Active' }}
                            </p>
                        </div>
                    </div>
                @elseif($recentPayments->isEmpty() && $recentRegistrations->isEmpty())
                    <p class="py-8 text-center text-xs text-slate-400">No recent member activity recorded yet.</p>
                @endif
            </div>

            <!-- Quick Pay Dues Shortcut Banner -->
            @if($member)
                <div class="pt-2">
                    <a href="{{ route('member.payments') }}" class="btn-pyc-primary w-full">
                        Pay Monthly Dues ({{ $monthlyDues->format() }})
                    </a>
                </div>
            @endif
        </div>
    </div>
@endcomponent
