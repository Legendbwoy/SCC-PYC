<div class="flex flex-wrap items-center gap-2 border-b border-slate-200/80 pb-3">
    <a href="{{ route('membership.index') }}"
       class="px-4 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('membership.index') || request()->routeIs('membership.show') ? 'bg-[#0F2D59] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-sky-50/60' }}">
        Membership Register &amp; Applications
    </a>
    @can('google_sync.view')
        <a href="{{ route('membership.google-sync') }}"
           class="px-4 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('membership.google-sync*') ? 'bg-[#0F2D59] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-sky-50/60' }}">
            Google Forms Sync
        </a>
    @endcan
</div>
