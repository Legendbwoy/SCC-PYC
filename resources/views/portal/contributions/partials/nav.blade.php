<div class="flex flex-wrap items-center gap-2 border-b border-slate-200/80 pb-3">
    <a href="{{ route('contributions.index') }}"
       class="px-4 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('contributions.index') ? 'bg-[#0F2D59] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-sky-50/60' }}">
        Overview, Dues Generator &amp; Campaigns
    </a>
    <a href="{{ route('contributions.obligations') }}"
       class="px-4 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('contributions.obligations') ? 'bg-[#0F2D59] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-sky-50/60' }}">
        Member Obligations &amp; Adjustments
    </a>
</div>
