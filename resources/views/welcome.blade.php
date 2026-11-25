<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pycBranding['organization_name'] ?? 'St. Cecilia Catholic Church — Parish Youth Council (PYC)' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#F3F6FB] font-sans antialiased text-slate-900 pb-16 md:pb-0">

    <!-- Hero & Top Navigation -->
    <header id="home" class="relative overflow-hidden bg-gradient-to-br from-[#081E3F] via-[#0F3163] to-[#1A4E96] text-white">
        <!-- Atmospheric Light & Parish Church Silhouette -->
        <div class="pointer-events-none absolute inset-0 opacity-25">
            <div class="absolute -top-24 right-12 h-96 w-96 rounded-full bg-sky-300/30 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-80 w-[34rem] rounded-full bg-amber-300/20 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Navbar -->
            <nav class="flex items-center justify-between py-5">
                <a href="{{ route('home') }}" class="focus:outline-none">
                    <x-pyc-logo variant="dark" subtitle="Parish Youth Council" />
                </a>

                <div class="hidden md:flex items-center gap-8 text-sm font-medium text-sky-100">
                    <a href="#home" class="text-white font-semibold hover:text-[#F5C542] transition">Home</a>
                    <a href="#about" class="hover:text-[#F5C542] transition">About</a>
                    <a href="#events" class="hover:text-[#F5C542] transition">Events</a>
                    <a href="#membership" class="hover:text-[#F5C542] transition">Membership</a>
                    <a href="#support" class="hover:text-[#F5C542] transition">Give</a>
                    <a href="#contact" class="hover:text-[#F5C542] transition">Contact</a>
                </div>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-pyc-gold">
                            Portal Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-pyc-gold">
                            Login
                        </a>
                    @endauth
                </div>
            </nav>

            <!-- Hero Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center pt-6 pb-16 lg:pt-12 lg:pb-24">
                <div class="lg:col-span-7 space-y-5">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1 text-xs font-semibold text-amber-200 ring-1 ring-white/15">
                        <span>{{ $pycBranding['parish_name'] ?? 'St. Cecilia Catholic Church' }}</span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-[3.35rem] font-extrabold tracking-tight leading-[1.12] text-white">
                        Parish Youth Council<br>
                        <span class="text-sky-100">(PYC)</span>
                    </h1>

                    <p class="font-serif italic text-xl sm:text-2xl text-amber-200/95 tracking-wide">
                        Faith &bull; Service &bull; Leadership
                    </p>

                    <p class="max-w-xl text-base sm:text-lg text-sky-100/90 leading-relaxed">
                        Empowering our youth to grow in faith, serve our parish, and build a vibrant Christ-centered community.
                    </p>

                    <div class="flex flex-wrap items-center gap-4 pt-2">
                        <a href="#membership" class="btn-pyc-gold px-7 py-3 text-sm">
                            Join the PYC
                        </a>
                        <a href="#about" class="inline-flex items-center justify-center rounded-full border-2 border-white/75 bg-white/5 px-7 py-2.5 text-sm font-semibold text-white backdrop-blur-xs transition hover:bg-white/15">
                            Learn More
                        </a>
                    </div>
                </div>

                <!-- Parish Church & PYC Youth Fellowship Visual Card -->
                <div class="lg:col-span-5">
                    <div class="relative rounded-3xl bg-gradient-to-b from-sky-200/20 via-white/10 to-[#081E3F]/80 p-3 ring-1 ring-white/20 shadow-2xl backdrop-blur-sm">
                        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-b from-sky-200 via-amber-100 to-emerald-800 aspect-[4/3]">
                            <svg viewBox="0 0 480 360" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-full w-full" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                                <defs>
                                    <linearGradient id="heroSky" x1="240" y1="0" x2="240" y2="260" gradientUnits="userSpaceOnUse">
                                        <stop offset="0%" stop-color="#60A5FA"/>
                                        <stop offset="55%" stop-color="#DBEAFE"/>
                                        <stop offset="100%" stop-color="#FEF3C7"/>
                                    </linearGradient>
                                </defs>
                                <rect width="480" height="360" fill="url(#heroSky)"/>
                                <!-- Soft Clouds -->
                                <ellipse cx="95" cy="68" rx="52" ry="18" fill="white" fill-opacity="0.7"/>
                                <ellipse cx="390" cy="58" rx="60" ry="20" fill="white" fill-opacity="0.65"/>
                                <!-- Greenery -->
                                <circle cx="72" cy="222" r="48" fill="#15803D"/>
                                <circle cx="115" cy="232" r="38" fill="#16A34A"/>
                                <circle cx="412" cy="220" r="50" fill="#15803D"/>
                                <circle cx="372" cy="232" r="38" fill="#16A34A"/>
                                <!-- St. Cecilia Parish Church Architecture -->
                                <path d="M240 62L148 134H332L240 62Z" fill="#92400E"/>
                                <rect x="160" y="134" width="160" height="110" fill="#FDE68A"/>
                                <path d="M240 72L168 134H312L240 72Z" fill="#F5DEB3"/>
                                <!-- Church Steeple Cross -->
                                <rect x="237" y="28" width="6" height="38" rx="2" fill="#B45309"/>
                                <rect x="225" y="38" width="30" height="6" rx="2" fill="#B45309"/>
                                <!-- Rose Window & Arched Entrance -->
                                <circle cx="240" cy="112" r="14" fill="#1E3A8A" stroke="#92400E" stroke-width="3"/>
                                <path d="M222 244V194C222 184 258 184 258 194V244H222Z" fill="#78350F"/>
                                <rect x="182" y="162" width="18" height="34" rx="9" fill="#1E40AF" opacity="0.75"/>
                                <rect x="280" y="162" width="18" height="34" rx="9" fill="#1E40AF" opacity="0.75"/>
                                <!-- Lawn -->
                                <rect y="242" width="480" height="118" fill="#166534"/>
                                <!-- United PYC Youth Silhouettes in Navy PYC Jerseys -->
                                @foreach([96, 152, 210, 268, 326, 382] as $idx => $cx)
                                    <circle cx="{{ $cx }}" cy="{{ 246 - ($idx % 2) * 4 }}" r="16" fill="#3B2314"/>
                                    <path d="M{{ $cx - 28 }} 360L{{ $cx - 24 }} 274C{{ $cx - 14 }} 265 {{ $cx + 14 }} 265 {{ $cx + 24 }} 274L{{ $cx + 28 }} 360H{{ $cx - 28 }}Z" fill="#0B2545" stroke="#1E40AF" stroke-width="1.5"/>
                                    <text x="{{ $cx }}" y="308" text-anchor="middle" fill="#FFFFFF" font-size="12" font-weight="800" font-family="sans-serif">PYC</text>
                                @endforeach
                                <!-- Arms Around Shoulders -->
                                <path d="M96 278H382" stroke="#0F2D59" stroke-width="9" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- 3-Pillar Feature Strip (Matching Reference Mockup) -->
    <section id="about" class="relative -mt-8 z-10 mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-lg ring-1 ring-slate-200/80 grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-200/80">
            <a href="#membership" class="group p-7 text-center hover:bg-sky-50/40 transition rounded-t-2xl md:rounded-l-2xl md:rounded-tr-none">
                <div class="mx-auto h-12 w-12 rounded-2xl bg-sky-50 text-[#0F2D59] flex items-center justify-center group-hover:scale-105 transition">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>
                    </svg>
                </div>
                <h2 class="mt-3 text-lg font-bold text-[#0F2D59]">Become a Member</h2>
                <p class="mt-1 text-sm text-slate-600">Join our vibrant parish youth community</p>
            </a>

            <a href="#events" class="group p-7 text-center hover:bg-sky-50/40 transition">
                <div class="mx-auto h-12 w-12 rounded-2xl bg-emerald-50 text-[#0F2D59] flex items-center justify-center group-hover:scale-105 transition">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                    </svg>
                </div>
                <h2 class="mt-3 text-lg font-bold text-[#0F2D59]">Attend Events</h2>
                <p class="mt-1 text-sm text-slate-600">Grow together in faith &amp; fellowship</p>
            </a>

            <a href="#support" class="group p-7 text-center hover:bg-sky-50/40 transition rounded-b-2xl md:rounded-r-2xl md:rounded-bl-none">
                <div class="mx-auto h-12 w-12 rounded-2xl bg-amber-50 text-[#0F2D59] flex items-center justify-center group-hover:scale-105 transition">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                    </svg>
                </div>
                <h2 class="mt-3 text-lg font-bold text-[#0F2D59]">Support the Mission</h2>
                <p class="mt-1 text-sm text-slate-600">Pay dues, give &amp; make an impact</p>
            </a>
        </div>
    </section>

    <!-- Upcoming Events & Membership Onboarding Section -->
    <main class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-14 space-y-14">
        <section id="events" class="space-y-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#1E549F]">Fellowship &amp; Spiritual Renewal</span>
                    <h2 class="mt-1 text-2xl font-bold text-[#0B2545]">Upcoming Parish Youth Events</h2>
                </div>
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="text-sm font-semibold text-[#1E549F] hover:underline">
                    Sign in to register &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($upcomingEvents as $ev)
                    <div class="pyc-card p-5 flex gap-4 items-center">
                        <x-pyc-event-banner class="h-24 w-24" />
                        <div class="min-w-0 space-y-1.5">
                            <span class="inline-block px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-semibold">
                                Open for Registration
                            </span>
                            <h3 class="font-bold text-slate-900 truncate">{{ $ev->title }}</h3>
                            <p class="text-xs text-slate-500">
                                {{ $ev->starts_at->timezone('Africa/Accra')->format('D, M j, Y • g:i A') }}
                            </p>
                            <p class="text-xs text-slate-600 truncate">{{ $ev->venue }}</p>
                        </div>
                    </div>
                @empty
                    <div class="pyc-card p-6 md:col-span-3 flex flex-col sm:flex-row items-center gap-6">
                        <x-pyc-event-banner class="h-28 w-36" />
                        <div class="space-y-2 text-center sm:text-left">
                            <span class="inline-block px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">
                                Parish Youth Calendar
                            </span>
                            <h3 class="text-lg font-bold text-[#0B2545]">Youth Retreats, General Meetings &amp; Outreach</h3>
                            <p class="text-sm text-slate-600 max-w-2xl">
                                Sign in to your PYC Member Portal to view upcoming St. Cecilia Parish Youth Council retreats, Bible study fellowships, and register in one click.
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>

        <!-- Membership & Dues Info Grid -->
        <section id="membership" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="pyc-card p-7 space-y-4">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-[#0F2D59]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-[#0B2545]">Official PYC Membership Onboarding</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    New members register through the official St. Cecilia Parish Youth Council registration form. Once verified by the General Secretary, you receive your official <code class="font-mono font-semibold text-[#0F2D59]">PYC-YYYY-NNNN</code> member number and a single-use portal activation link via SMS and email.
                </p>
                <div class="pt-1">
                    <a href="{{ route('login') }}" class="btn-pyc-primary">
                        Access Member Portal
                    </a>
                </div>
            </div>

            <div id="support" class="pyc-card p-7 space-y-4">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-[#0B2545]">Monthly Youth Dues &amp; Transparent Receipts</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    Manage your <strong>GHS 10.00</strong> monthly youth dues and parish campaign contributions online via Paystack (Card or Mobile Money) or verified cash collection—with instant <code class="font-mono font-semibold text-[#0F2D59]">PYC-RCT-*</code> official PDF receipts.
                </p>
                <div class="pt-1">
                    <a href="{{ auth()->check() ? route('member.payments') : route('login') }}" class="btn-pyc-outline">
                        Pay Dues / View Statement
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- Deep Navy Footer Strip (Matching Reference Mockup) -->
    <footer id="contact" class="bg-[#081E3F] text-sky-100/90 py-6 border-t border-white/10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <p class="font-semibold text-white">
                {{ $pycBranding['organization_name'] ?? 'St. Cecilia Catholic Parish Youth Council' }}
                <span class="mx-2 text-sky-300/50">|</span>
                <span class="text-sky-200 font-normal">United in Christ, empowered to serve</span>
            </p>
            <p class="text-sky-200/80">
                {{ $pycBranding['address'] ?? 'St. Cecilia Catholic Church, Accra' }} &bull; {{ $pycBranding['currency'] ?? 'GHS' }}
            </p>
        </div>
    </footer>

    <!-- Mobile Sticky Bottom Tab Bar (Matching Phone Mockup) -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 py-2 flex items-center justify-around text-[11px] font-semibold text-slate-500">
        <a href="#home" class="flex flex-col items-center gap-0.5 text-[#0F2D59]">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z"/><path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z"/></svg>
            <span>Home</span>
        </a>
        <a href="#events" class="flex flex-col items-center gap-0.5 hover:text-[#0F2D59]">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
            <span>Events</span>
        </a>
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="flex flex-col items-center gap-0.5 hover:text-[#0F2D59]">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
            <span>{{ auth()->check() ? 'Portal' : 'Login' }}</span>
        </a>
        <a href="#membership" class="flex flex-col items-center gap-0.5 hover:text-[#0F2D59]">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
            <span>More</span>
        </a>
    </nav>
</body>
</html>
