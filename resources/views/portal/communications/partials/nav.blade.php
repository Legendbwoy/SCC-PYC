<div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3">
    <a href="{{ route('communications.index') }}"
       class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('communications.index') ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
        Overview &amp; Test Send
    </a>
    <a href="{{ route('communications.templates') }}"
       class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('communications.templates') ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
        Templates &amp; Versions
    </a>
    @can('notifications.view_delivery_logs')
        <a href="{{ route('communications.deliveries') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('communications.deliveries') ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
            Delivery Logs &amp; Retries
        </a>
    @endcan
    <a href="{{ route('communications.campaigns') }}"
       class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('communications.campaigns') ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
        Bulk Campaigns
    </a>
</div>
