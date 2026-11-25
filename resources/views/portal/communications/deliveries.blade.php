@component('layouts.app', [
    'title' => 'Delivery Logs',
    'heading' => 'Channel Delivery Logs & Controlled Retry Queue',
    'subheading' => 'Independent SMS and email delivery records with template version lineage and retry controls.',
])
    @include('portal.communications.partials.nav')

    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
        <form method="GET" action="{{ route('communications.deliveries') }}" class="flex flex-wrap items-center gap-3">
            <select name="channel" class="rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                <option value="">All Channels</option>
                @foreach(['sms', 'email', 'in_app'] as $ch)
                    <option value="{{ $ch }}" @selected($selectedChannel === $ch)>{{ strtoupper($ch) }}</option>
                @endforeach
            </select>

            <select name="status" class="rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                <option value="">All Statuses</option>
                @foreach(['queued', 'processing', 'accepted', 'sent', 'delivered', 'retrying', 'failed', 'cancelled', 'unknown'] as $st)
                    <option value="{{ $st }}" @selected($selectedStatus === $st)>{{ ucfirst($st) }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 cursor-pointer">
                Filter Logs
            </button>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">ID / Channel</th>
                        <th class="px-5 py-3 text-left font-semibold">Recipient</th>
                        <th class="px-5 py-3 text-left font-semibold">Template / Version</th>
                        <th class="px-5 py-3 text-left font-semibold">Status &amp; Attempts</th>
                        <th class="px-5 py-3 text-left font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($deliveries as $delivery)
                        <tr class="align-top">
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs">
                                <p class="font-mono text-slate-500">#{{ $delivery->id }}</p>
                                <span class="px-2 py-0.5 rounded bg-slate-100 font-semibold uppercase text-[10px] text-slate-700">{{ $delivery->channel }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-xs">
                                <p class="font-medium text-slate-900">{{ $delivery->recipient_name ?? 'Recipient' }}</p>
                                <p class="font-mono text-slate-500">{{ $delivery->recipient_address }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-xs">
                                <p class="font-mono text-teal-800">{{ $delivery->log?->template_code ?? ($delivery->log?->event_name ?? 'custom') }}</p>
                                @if($delivery->templateVersion)
                                    <p class="text-[11px] text-slate-400">Version v{{ $delivery->templateVersion->version_number }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs">
                                <span class="px-2 py-0.5 rounded-full font-semibold text-[11px] {{ in_array($delivery->status, ['sent', 'accepted', 'delivered']) ? 'bg-emerald-50 text-emerald-700' : ($delivery->status === 'failed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-800') }}">
                                    {{ $delivery->status }}
                                </span>
                                <p class="mt-1 text-[11px] text-slate-500">Attempts: {{ $delivery->attempts }}</p>
                                @if($delivery->last_error)
                                    <p class="mt-1 text-[11px] text-rose-600 max-w-xs truncate" title="{{ $delivery->last_error }}">{{ $delivery->last_error }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs">
                                @if(in_array($delivery->status, ['failed', 'retrying', 'unknown']) && auth()->user()->can('notifications.retry_failed'))
                                    <form method="POST" action="{{ route('communications.deliveries.retry', $delivery) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 cursor-pointer">
                                            Retry
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">No delivery records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-slate-100">
            {{ $deliveries->links() }}
        </div>
    </div>
@endcomponent
