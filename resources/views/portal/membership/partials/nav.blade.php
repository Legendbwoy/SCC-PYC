<div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3">
    <a href="{{ route('membership.index') }}"
       class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('membership.index') || request()->routeIs('membership.show') ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
        Membership Register
    </a>
    @can('google_sync.view')
        <a href="{{ route('membership.google-sync') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('membership.google-sync*') ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
            Google Forms Sync
        </a>
    @endcan
</div>
