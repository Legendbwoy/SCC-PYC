@component('layouts.app', [
    'title' => 'Audit Trail',
    'heading' => 'Immutable System & Security Audit Trail',
    'subheading' => 'Read-only chronological record of authentication, user administration, configuration, and financial events.',
])
    <div class="pyc-card p-4">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="flex flex-wrap items-center gap-3">
            <select name="category" class="pyc-input w-auto py-2 bg-white">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected($selectedCategory === $cat)>{{ ucfirst($cat) }}</option>
                @endforeach
            </select>

            <input type="text" name="q" value="{{ $search }}" placeholder="Search event or description..."
                   class="pyc-input flex-1 min-w-[220px] py-2">

            <button type="submit" class="btn-pyc-primary py-2 px-4 text-xs">
                Filter
            </button>

            @if($selectedCategory !== '' || $search !== '')
                <a href="{{ route('admin.audit-logs.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-800">
                    Clear filters
                </a>
            @endif
        </form>
    </div>

    <div class="pyc-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">ID</th>
                        <th class="px-5 py-3 text-left font-semibold">Timestamp (Africa/Accra)</th>
                        <th class="px-5 py-3 text-left font-semibold">Category</th>
                        <th class="px-5 py-3 text-left font-semibold">Event</th>
                        <th class="px-5 py-3 text-left font-semibold">Actor / IP</th>
                        <th class="px-5 py-3 text-left font-semibold">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="align-top">
                            <td class="px-5 py-3 font-mono text-xs text-slate-400">#{{ $log->id }}</td>
                            <td class="px-5 py-3 whitespace-nowrap text-xs text-slate-600">
                                {{ $log->created_at?->timezone('Africa/Accra')->format('d M Y H:i:s') }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $log->category }}
                                </span>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap font-mono text-xs font-semibold text-[#1E549F]">{{ $log->event }}</td>
                            <td class="px-5 py-3 whitespace-nowrap text-xs">
                                <p class="font-medium text-slate-800">{{ $log->actor?->name ?? 'System' }}</p>
                                @if($log->ip_address)
                                    <p class="text-slate-400 font-mono">{{ $log->ip_address }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-700">
                                <p>{{ $log->description }}</p>
                                @if($log->old_values || $log->new_values || $log->metadata)
                                    <details class="mt-1.5">
                                        <summary class="cursor-pointer text-[11px] font-semibold text-[#1E549F]">Inspect payload</summary>
                                        <pre class="mt-1 p-2 rounded-lg bg-slate-900 text-slate-100 text-[11px] overflow-x-auto">{{ json_encode(array_filter(['old' => $log->old_values, 'new' => $log->new_values, 'meta' => $log->metadata]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">
                                No matching audit log entries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-slate-100">
            {{ $logs->links() }}
        </div>
    </div>
@endcomponent
