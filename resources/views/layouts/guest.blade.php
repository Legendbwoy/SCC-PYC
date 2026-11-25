<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sign In' }} — {{ $pycBranding['short_name'] ?? 'SCC-PYC' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --pyc-primary: {{ $pycBranding['primary_color'] ?? '#0f766e' }};
            --pyc-accent: {{ $pycBranding['accent_color'] ?? '#b45309' }};
        }
    </style>
</head>
<body class="min-h-full font-sans antialiased text-slate-900 flex flex-col justify-center py-10 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4 text-center">
        <div class="mx-auto h-14 w-14 rounded-2xl flex items-center justify-center text-white font-bold text-xl shadow-md" style="background-color: var(--pyc-primary);">
            PYC
        </div>
        <p class="mt-4 text-xs font-semibold uppercase tracking-widest text-teal-700">
            {{ $pycBranding['parish_name'] ?? 'St. Cecilia Catholic Church' }}
        </p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
            Parish Youth Council Portal
        </h1>
        @if(!empty($pycBranding['tagline']))
            <p class="mt-1 text-sm text-slate-500">{{ $pycBranding['tagline'] }}</p>
        @endif
    </div>

    <div class="mt-7 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="bg-white py-8 px-6 shadow-sm ring-1 ring-slate-200 rounded-2xl sm:px-9">
            @if (session('status'))
                <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </div>

        <p class="mt-6 text-center text-xs text-slate-500">
            {{ $pycBranding['organization_name'] ?? 'St. Cecilia Catholic Church — Parish Youth Council' }} &bull; Timezone: {{ $pycBranding['timezone'] ?? 'Africa/Accra' }} ({{ $pycBranding['currency'] ?? 'GHS' }})
        </p>
    </div>
</body>
</html>
