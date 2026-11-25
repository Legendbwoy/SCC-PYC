@props([
    'variant' => 'dark', // 'dark' (navy bg), 'light' (centered auth card), 'inline-light'
    'subtitle' => 'Parish Youth Council',
    'size' => 'md', // 'sm' | 'md' | 'lg'
])

@php
    $iconDimensions = match ($size) {
        'sm' => 'h-9 w-9',
        'lg' => 'h-16 w-16',
        default => 'h-11 w-11',
    };
    $customLogoUrl = $pycBranding['logo_url'] ?? null;
@endphp

@if($variant === 'light')
    <div {{ $attributes->merge(['class' => 'inline-flex flex-col items-center text-center select-none']) }}>
        @if($customLogoUrl)
            <img src="{{ $customLogoUrl }}" alt="PYC Logo" class="{{ $size === 'lg' ? 'h-16' : 'h-14' }} w-auto object-contain">
        @else
            <svg viewBox="0 0 80 72" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ $size === 'lg' ? 'h-16 w-18' : 'h-14 w-16' }} drop-shadow-xs" aria-hidden="true">
                <path d="M12 61C27 55 53 55 68 61" stroke="#1E549F" stroke-width="3" stroke-linecap="round" opacity="0.3"/>
                <rect x="37" y="6" width="6" height="46" rx="3" fill="#F59E0B"/>
                <rect x="25" y="17" width="30" height="6" rx="3" fill="#FBBF24"/>
                <circle cx="23" cy="31" r="5" fill="#1D4ED8"/>
                <path d="M13 26C17 31 22 36 33 34L29 53L21 62M25 50L15 60" stroke="#1D4ED8" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="57" cy="31" r="5" fill="#0F2D59"/>
                <path d="M67 26C63 31 58 36 47 34L51 53L59 62M55 50L65 60" stroke="#0F2D59" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        @endif
        <span class="mt-1 text-2xl font-extrabold tracking-tight text-[#0F2D59] leading-none">PYC</span>
        <span class="mt-0.5 text-xs font-semibold text-slate-600 tracking-tight">{{ $subtitle }}</span>
    </div>
@else
    <div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 select-none']) }}>
        @if($customLogoUrl)
            <img src="{{ $customLogoUrl }}" alt="PYC Logo" class="{{ $iconDimensions }} rounded-lg object-contain bg-white/10 p-0.5 shrink-0">
        @else
            <svg viewBox="0 0 76 68" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ $iconDimensions }} shrink-0" aria-hidden="true">
                <path d="M10 58C26 52 50 52 66 58" stroke="#FBBF24" stroke-width="3" stroke-linecap="round" opacity="0.85"/>
                <rect x="35" y="5" width="6" height="44" rx="3" fill="#FBBF24"/>
                <rect x="23" y="16" width="30" height="6" rx="3" fill="#FDE047"/>
                <circle cx="21" cy="30" r="4.8" fill="#38BDF8"/>
                <path d="M11 25C16 30 21 35 31 33L27 50L19 58M24 47L14 56" stroke="#38BDF8" stroke-width="4.3" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="55" cy="30" r="4.8" fill="#4ADE80"/>
                <path d="M65 25C60 30 55 35 45 33L49 50L57 58M52 47L62 56" stroke="#4ADE80" stroke-width="4.3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        @endif
        <div class="min-w-0 leading-tight">
            <div class="text-xl font-extrabold tracking-tight {{ $variant === 'inline-light' ? 'text-[#0F2D59]' : 'text-white' }}">PYC</div>
            <div class="text-[11px] font-medium {{ $variant === 'inline-light' ? 'text-slate-500' : 'text-sky-200/90' }} truncate">{{ $subtitle }}</div>
        </div>
    </div>
@endif
