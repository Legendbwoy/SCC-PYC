<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sign In' }} — {{ $pycBranding['short_name'] ?? 'SCC-PYC' }}</title>
    @if(!empty($pycBranding['favicon_url']))
        <link rel="icon" href="{{ $pycBranding['favicon_url'] }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --pyc-primary: {{ $pycBranding['primary_color'] ?? '#0F2D59' }};
            --pyc-accent: {{ $pycBranding['accent_color'] ?? '#F5C542' }};
        }
    </style>
</head>
<body class="relative min-h-screen font-sans antialiased text-slate-900 flex flex-col justify-center py-10 px-4 sm:px-6 lg:px-8 overflow-x-hidden bg-gradient-to-b from-[#D9E6F7] via-[#E8F0FB] to-[#C8DCF4]">

    <!-- Subtle Atmospheric Clouds & Parish Church Horizon Silhouette (or Custom Login Backdrop) -->
    <div class="pointer-events-none fixed inset-0 overflow-hidden z-0" aria-hidden="true">
        @if(!empty($pycBranding['login_bg_url']))
            <img src="{{ $pycBranding['login_bg_url'] }}" alt="" class="h-full w-full object-cover opacity-30">
            <div class="absolute inset-0 bg-gradient-to-b from-[#D9E6F7]/75 via-[#E8F0FB]/70 to-[#C8DCF4]/85"></div>
        @else
            <div class="absolute -top-24 left-1/2 -translate-x-1/2 h-96 w-[44rem] rounded-full bg-white/60 blur-3xl"></div>
            <svg viewBox="0 0 1440 260" fill="none" xmlns="http://www.w3.org/2000/svg" class="absolute bottom-0 inset-x-0 w-full h-44 sm:h-56 object-cover opacity-35">
                <path d="M0 210C220 180 430 195 660 185C900 175 1160 155 1440 190V260H0V210Z" fill="#8FB2DC"/>
                <path d="M720 68L704 122H736L720 68Z" fill="#6E96C8"/>
                <rect x="718" y="46" width="4" height="24" fill="#6E96C8"/>
                <rect x="711" y="53" width="18" height="4" fill="#6E96C8"/>
                <rect x="688" y="122" width="64" height="68" fill="#7BA2D1"/>
                <path d="M0 228C340 202 650 220 980 206C1200 196 1340 205 1440 218V260H0V228Z" fill="#6E96C8"/>
            </svg>
        @endif
    </div>

    <!-- Top Back Link -->
    <div class="relative z-10 sm:mx-auto sm:w-full sm:max-w-[420px] mb-4 flex justify-between items-center px-1">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0F2D59]/80 hover:text-[#0F2D59] transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            <span>Back to Home</span>
        </a>
        <span class="text-[11px] font-semibold text-[#0F2D59]/70">{{ $pycBranding['parish_name'] ?? 'St. Cecilia Parish' }}</span>
    </div>

    <!-- Centered Auth Card -->
    <div class="relative z-10 sm:mx-auto sm:w-full sm:max-w-[420px]">
        <div class="bg-white/95 backdrop-blur-md py-9 px-6 shadow-[0_20px_50px_-15px_rgba(15,45,89,0.18)] ring-1 ring-white/80 rounded-3xl sm:px-9">
            <!-- PYC Crest Header -->
            <div class="text-center mb-6">
                <a href="{{ route('home') }}" class="inline-block focus:outline-none">
                    <x-pyc-logo variant="light" subtitle="Parish Youth Council" />
                </a>
            </div>

            @if (session('status'))
                <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-xs font-medium text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-xs text-rose-800">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </div>

        <p class="mt-5 text-center text-[11px] font-medium text-[#0F2D59]/75">
            {{ $pycBranding['organization_name'] ?? 'St. Cecilia Catholic Church — Parish Youth Council' }}
        </p>
    </div>
</body>
</html>
