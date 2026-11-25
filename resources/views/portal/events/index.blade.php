@component('layouts.app', [
    'title' => 'Parish Youth Events',
    'heading' => 'Parish Youth Events, Registrations & Contributions',
    'subheading' => 'Create events, link optional contributions to the shared obligation engine, publish announcements, and track attendance.',
])
    @can('notifications.send')
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <h2 class="text-base font-bold text-slate-900 mb-4">Create New Parish Youth Event</h2>
            <form method="POST" action="{{ route('events.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @csrf
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700">Event Title</label>
                    <input type="text" name="title" required placeholder="Annual Lenten Youth Retreat 2026"
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Venue</label>
                    <input type="text" name="venue" required placeholder="St. Cecilia Parish Hall"
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Start Date &amp; Time</label>
                    <input type="datetime-local" name="starts_at" required
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Capacity (Optional)</label>
                    <input type="number" name="capacity" min="1" placeholder="Unlimited"
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Optional Event Contribution (GHS)</label>
                    <input type="text" name="contribution_ghs" placeholder="0.00 (Free) or e.g. 25.00"
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono">
                </div>
                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-700">Description</label>
                    <textarea name="description" rows="2" class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm"></textarea>
                </div>
                <div class="sm:col-span-3 flex justify-end">
                    <button type="submit" class="rounded-xl px-5 py-2.5 text-xs font-semibold text-white cursor-pointer" style="background-color: var(--pyc-primary);">
                        Create &amp; Publish Event
                    </button>
                </div>
            </form>
        </div>
    @endcan

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-base font-bold text-slate-900">PYC Events Register</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Reference / Title</th>
                        <th class="px-5 py-3 text-left font-semibold">Date &amp; Venue</th>
                        <th class="px-5 py-3 text-left font-semibold">Contribution</th>
                        <th class="px-5 py-3 text-center font-semibold">Reg / Attended</th>
                        <th class="px-5 py-3 text-right font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($events as $ev)
                        <tr>
                            <td class="px-5 py-3.5 text-xs">
                                <p class="font-mono font-semibold text-teal-800">{{ $ev->reference }}</p>
                                <p class="font-bold text-slate-900">{{ $ev->title }}</p>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold capitalize bg-slate-100 text-slate-700">{{ $ev->status }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-700">
                                <p>{{ $ev->starts_at->timezone('Africa/Accra')->format('d M Y, H:i') }}</p>
                                <p class="text-slate-500">{{ $ev->venue }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-xs font-mono">
                                {{ $ev->contributionCampaign ? $ev->contributionCampaign->amountMoney()->format() : 'Free' }}
                            </td>
                            <td class="px-5 py-3.5 text-center font-mono text-xs">
                                {{ $ev->registered_count }} / {{ $ev->attended_count }}
                            </td>
                            <td class="px-5 py-3.5 text-right text-xs">
                                <a href="{{ route('events.show', $ev) }}" class="font-semibold text-teal-700 hover:underline">Manage &rarr;</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">No events created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endcomponent
