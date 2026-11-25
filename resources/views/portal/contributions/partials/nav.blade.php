<div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3">
    <a href="{{ route('contributions.index') }}"
       class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('contributions.index') ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
        Overview, Dues Generator &amp; Campaigns
    </a>
    <a href="{{ route('contributions.obligations') }}"
       class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('contributions.obligations') ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
        Member Obligations &amp; Adjustments
    </a>
</div>
