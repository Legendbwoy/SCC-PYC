@component('layouts.app', [
    'title' => $module['title'],
    'heading' => $module['title'],
    'subheading' => 'Roadmap Target: ' . $module['sprint'] . ' — Module Contract & Architecture Initialized',
])
    <div class="pyc-card p-6 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <span class="px-3 py-1 rounded-full bg-sky-50 text-[#0F2D59] text-xs font-bold">
                Scheduled in {{ $module['sprint'] }}
            </span>
            <span class="text-xs text-slate-500 font-mono">
                Required Permission: {{ $module['permission'] }}
            </span>
        </div>

        <p class="text-sm text-slate-700 leading-relaxed">
            {{ $module['summary'] }}
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
            <div class="rounded-xl bg-slate-50 border border-slate-100 p-5">
                <h3 class="text-sm font-bold text-slate-900 mb-3">Planned Deliverables</h3>
                <ul class="space-y-2 text-xs text-slate-600 list-disc pl-4">
                    @foreach($module['deliverables'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="rounded-xl bg-amber-50/60 border border-amber-200/70 p-5">
                <h3 class="text-sm font-bold text-amber-950 mb-3">Enforced Architectural Invariants</h3>
                <ul class="space-y-2 text-xs text-amber-900 list-disc pl-4">
                    @foreach($module['invariants'] as $invariant)
                        <li>{{ $invariant }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endcomponent
