@php
    $featuredEvent = $events->first();
@endphp

@component('layouts.app', [
    'title' => 'Events Management',
    'heading' => 'Events Management',
    'subheading' => 'Plan and manage PYC events, optional contributions, announcements, and attendance.',
])
    @can('notifications.send')
        @slot('headerActions')
            <button type="button"
                    onclick="document.getElementById('pycCreateEventPanel').classList.toggle('hidden');"
                    class="btn-pyc-primary">
                <span>+ Create Event</span>
            </button>
        @endslot

        <!-- Collapsible Create Event Card -->
        <div id="pycCreateEventPanel" class="{{ $errors->any() ? '' : 'hidden' }} pyc-card p-6 sm:p-7">
            <h2 class="text-base font-extrabold text-slate-900 mb-4">Create New Parish Youth Event</h2>
            <form method="POST" action="{{ route('events.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @csrf
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Event Title</label>
                    <input type="text" name="title" required placeholder="Youth Retreat 2026" class="pyc-input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Venue</label>
                    <input type="text" name="venue" required placeholder="St. Cecilia Parish Hall" class="pyc-input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Start Date &amp; Time</label>
                    <input type="datetime-local" name="starts_at" required class="pyc-input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Capacity (Optional)</label>
                    <input type="number" name="capacity" min="1" placeholder="Unlimited" class="pyc-input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Optional Event Contribution (GHS)</label>
                    <input type="text" name="contribution_ghs" placeholder="0.00 (Free) or e.g. 25.00" class="pyc-input font-mono">
                </div>
                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="2" class="pyc-input"></textarea>
                </div>
                <div class="sm:col-span-3 flex justify-end gap-2">
                    <button type="submit" class="btn-pyc-primary px-6">
                        Create &amp; Publish Event
                    </button>
                </div>
            </form>
        </div>
    @endcan

    <!-- Spotlight Row: Featured Event + Upcoming Events Sidebar (Matching Bottom-Left Reference Mockup) -->
    @if($featuredEvent)
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-7 pyc-card p-6 flex flex-col sm:flex-row gap-6 items-start sm:items-center">
                <x-pyc-event-banner class="h-44 w-full sm:w-40" />
                <div class="flex-1 min-w-0 space-y-2.5">
                    <p class="font-mono text-xs font-bold text-[#1E549F]">{{ $featuredEvent->reference }}</p>
                    <h2 class="text-xl font-extrabold text-slate-900">{{ $featuredEvent->title }}</h2>
                    <div class="space-y-1 text-xs text-slate-600">
                        <p class="font-semibold text-slate-800">{{ $featuredEvent->starts_at->timezone('Africa/Accra')->format('D, M j, Y • g:i A') }}</p>
                        <p>{{ $featuredEvent->venue }}</p>
                    </div>
                    <div class="pt-1">
                        <span class="inline-block px-3.5 py-1 rounded-full text-xs font-bold {{ $featuredEvent->status === 'cancelled' ? 'bg-rose-100 text-rose-700' : 'bg-[#22C55E] text-white' }}">
                            {{ $featuredEvent->status === 'cancelled' ? 'Cancelled' : 'Open for Registration' }}
                        </span>
                    </div>
                    <div class="pt-2 flex flex-wrap gap-2.5">
                        <a href="{{ route('events.show', $featuredEvent) }}" class="btn-pyc-outline py-2 px-4 text-xs">
                            View Details &amp; Attendance
                        </a>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 pyc-card p-6 space-y-4">
                <h3 class="text-base font-extrabold text-slate-900">Upcoming Events</h3>
                <div class="divide-y divide-slate-100">
                    @foreach($events->take(4) as $sideEv)
                        <a href="{{ route('events.show', $sideEv) }}" class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50/80 rounded-xl px-2 -mx-2 transition">
                            <div class="flex items-center gap-3 min-w-0">
                                <x-pyc-event-banner class="h-10 w-10 rounded-lg" />
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-900 truncate">{{ $sideEv->title }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ $sideEv->venue }}</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-[#1E549F] shrink-0">
                                {{ $sideEv->starts_at->timezone('Africa/Accra')->format('M j') }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Full Events Register Table -->
    <div class="pyc-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-extrabold text-slate-900">PYC Events Register</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200/80 text-sm">
                <thead class="bg-slate-50/80 text-xs uppercase text-slate-500">
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
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3.5 text-xs">
                                <p class="font-mono font-bold text-[#1E549F]">{{ $ev->reference }}</p>
                                <p class="font-bold text-slate-900">{{ $ev->title }}</p>
                                <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold capitalize {{ $ev->status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">{{ $ev->status }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-700">
                                <p class="font-semibold">{{ $ev->starts_at->timezone('Africa/Accra')->format('d M Y, H:i') }}</p>
                                <p class="text-slate-500">{{ $ev->venue }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-xs font-mono font-semibold">
                                {{ $ev->contributionCampaign ? $ev->contributionCampaign->amountMoney()->format() : 'Free' }}
                            </td>
                            <td class="px-5 py-3.5 text-center font-mono text-xs font-bold">
                                {{ $ev->registered_count }} / {{ $ev->attended_count }}
                            </td>
                            <td class="px-5 py-3.5 text-right text-xs">
                                <a href="{{ route('events.show', $ev) }}" class="font-bold text-[#1E549F] hover:underline">Manage &rarr;</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">No events created yet. Click <strong>+ Create Event</strong> above to publish one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endcomponent
