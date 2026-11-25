<div class="flex flex-wrap items-center gap-2 border-b border-slate-200/80 pb-3">
    <a href="{{ route('communications.index') }}"
       class="px-4 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('communications.index') ? 'bg-[#0F2D59] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-sky-50/60' }}">
        Overview &amp; Test Send
    </a>
    <a href="{{ route('communications.templates') }}"
       class="px-4 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('communications.templates') ? 'bg-[#0F2D59] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-sky-50/60' }}">
        Templates &amp; Versions
    </a>
    @can('notifications.view_delivery_logs')
        <a href="{{ route('communications.deliveries') }}"
           class="px-4 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('communications.deliveries') ? 'bg-[#0F2D59] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-sky-50/60' }}">
            Delivery Logs &amp; Retries
        </a>
    @endcan
    <a href="{{ route('communications.campaigns') }}"
       class="px-4 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('communications.campaigns') ? 'bg-[#0F2D59] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-sky-50/60' }}">
        Bulk Campaigns
    </a>
</div>
