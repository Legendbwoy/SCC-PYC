@component('layouts.app', [
    'title' => 'Google Forms Sync',
    'heading' => 'Google Forms / Sheets Synchronization Console',
    'subheading' => 'Idempotent import of Google Form membership submissions into pending PYC applications.',
])
    @include('portal.membership.partials.nav')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900">Linked Response Spreadsheet</h2>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $credentialsConfigured ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">
                    {{ $credentialsConfigured ? 'Credentials Ready' : 'Set GOOGLE_SERVICE_ACCOUNT_JSON_PATH in .env' }}
                </span>
            </div>

            <form method="POST" action="{{ route('membership.google-sync.connection') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Google Spreadsheet ID</label>
                        <input type="text" name="spreadsheet_id" value="{{ old('spreadsheet_id', $connection->spreadsheet_id) }}" required
                               placeholder="1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Sheet Tab &amp; Cell Range</label>
                        <input type="text" name="sheet_range" value="{{ old('sheet_range', $connection->sheet_range) }}" required
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono">
                    </div>
                </div>
                @can('google_sync.run')
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800 cursor-pointer">
                        Save Spreadsheet Mapping
                    </button>
                @endcan
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs flex flex-col justify-between space-y-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Manual &amp; Scheduled Sync</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Scheduled sync runs every 15 minutes via Laravel Scheduler. Re-running never creates duplicate member applications.
                </p>
                <p class="mt-3 text-xs text-slate-600">
                    Last Synced: <strong>{{ $connection->last_synced_at?->timezone('Africa/Accra')->format('d M Y H:i:s') ?? 'Never' }}</strong>
                </p>
            </div>

            @can('google_sync.run')
                <form method="POST" action="{{ route('membership.google-sync.run') }}">
                    @csrf
                    <button type="submit" class="btn-pyc-primary w-full text-xs">
                        Synchronize Google Form Responses Now
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <!-- Sync Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-base font-bold text-slate-900">Synchronization Run History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Run / Time</th>
                        <th class="px-5 py-3 text-left font-semibold">Trigger</th>
                        <th class="px-5 py-3 text-left font-semibold">Status</th>
                        <th class="px-5 py-3 text-left font-semibold">Fetched / Imported / Duplicates / Skipped / Failed</th>
                        <th class="px-5 py-3 text-right font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($syncLogs as $log)
                        <tr>
                            <td class="px-5 py-3.5 text-xs">
                                <p class="font-mono text-slate-500">#{{ $log->id }}</p>
                                <p class="text-slate-600">{{ $log->started_at?->timezone('Africa/Accra')->format('d M Y H:i:s') }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-xs capitalize">{{ $log->trigger_type }} ({{ $log->initiator?->name ?? 'Scheduler' }})</td>
                            <td class="px-5 py-3.5 text-xs">
                                <span class="px-2 py-0.5 rounded-full font-semibold {{ $log->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($log->status === 'failed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-800') }}">
                                    {{ $log->status }}
                                </span>
                                @if($log->error_summary)
                                    <p class="mt-1 text-[11px] text-rose-600">{{ $log->error_summary }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-700">
                                {{ $log->fetched_count }} / {{ $log->imported_count }} / {{ $log->duplicate_flagged_count }} / {{ $log->skipped_count }} / {{ $log->failed_count }}
                            </td>
                            <td class="px-5 py-3.5 text-right text-xs">
                                @if($log->failed_count > 0 && auth()->user()->can('google_sync.run'))
                                    <form method="POST" action="{{ route('membership.google-sync.retry', $log) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white cursor-pointer">
                                            Retry Failed
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-400">No synchronization runs recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endcomponent
