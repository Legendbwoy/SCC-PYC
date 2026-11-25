@props([
    'class' => 'h-32 w-32',
])

<!-- Warm Sunrise Hilltop Cross & Youth Silhouette Illustration matching PYC Event Cards -->
<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-xl bg-gradient-to-b from-sky-200 via-amber-100 to-amber-300 shrink-0 select-none ' . $class]) }}>
    <svg viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-full w-full object-cover" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <defs>
            <linearGradient id="pycSky" x1="90" y1="0" x2="90" y2="180" gradientUnits="userSpaceOnUse">
                <stop offset="0%" stop-color="#93C5FD"/>
                <stop offset="48%" stop-color="#FDE68A"/>
                <stop offset="78%" stop-color="#F59E0B"/>
                <stop offset="100%" stop-color="#451A03"/>
            </linearGradient>
            <radialGradient id="pycSun" cx="50%" cy="66%" r="38%">
                <stop offset="0%" stop-color="#FEF9C3" stop-opacity="1"/>
                <stop offset="55%" stop-color="#FBBF24" stop-opacity="0.65"/>
                <stop offset="100%" stop-color="#F59E0B" stop-opacity="0"/>
            </radialGradient>
        </defs>
        <rect width="180" height="180" fill="url(#pycSky)"/>
        <circle cx="98" cy="118" r="54" fill="url(#pycSun)"/>
        <!-- Soft Clouds -->
        <ellipse cx="42" cy="42" rx="28" ry="8" fill="#FFFFFF" fill-opacity="0.45"/>
        <ellipse cx="140" cy="34" rx="24" ry="7" fill="#FFFFFF" fill-opacity="0.38"/>
        <!-- Hill Horizon -->
        <path d="M0 144C38 128 72 126 112 135C140 141 162 138 180 132V180H0V144Z" fill="#291509"/>
        <!-- Cross Silhouette -->
        <rect x="64" y="42" width="8" height="94" rx="1.5" fill="#1C0F07"/>
        <rect x="42" y="64" width="52" height="8" rx="1.5" fill="#1C0F07"/>
        <!-- Praying / Gathered Youth Silhouettes -->
        <circle cx="116" cy="115" r="5.5" fill="#1C0F07"/>
        <path d="M109 138C109 126 112 121 116 121C120 121 123 126 123 138H109Z" fill="#1C0F07"/>
        <circle cx="132" cy="119" r="4.8" fill="#1C0F07"/>
        <path d="M126 139C126 129 128.5 125 132 125C135.5 125 138 129 138 139H126Z" fill="#1C0F07"/>
    </svg>
</div>
