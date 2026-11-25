@component('layouts.app', [
    'title' => $event->title,
    'heading' => $event->title . " ({$event->reference})",
    'subheading' => $event->starts_at->timezone('Africa/Accra')->format('D, d M Y • H:i') . ' at ' . $event->venue,
])
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 pyc-card overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-base font-extrabold text-slate-900">Registrations &amp; Attendance ({{ $event->registrations->count() }})</h2>
                <a href="{{ route('events.index') }}" class="text-xs font-bold text-[#1E549F] hover:underline">&larr; All Events</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200/80 text-xs">
                    <thead class="bg-slate-50/80 uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">Member</th>
                            <th class="px-5 py-3 text-left font-semibold">Status</th>
                            <th class="px-5 py-3 text-left font-semibold">Contribution Obligation</th>
                            <th class="px-5 py-3 text-right font-semibold">Mark Attendance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($event->registrations as $reg)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3">
                                    <p class="font-bold text-slate-900">{{ $reg->member?->fullName() }}</p>
                                    <p class="font-mono text-[#1E549F]">{{ $reg->member?->member_number }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="px-2.5 py-0.5 rounded-full font-bold capitalize {{ $reg->status === 'attended' ? 'bg-emerald-50 text-emerald-700' : 'bg-sky-50 text-[#0F2D59]' }}">
                                        {{ $reg->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 font-mono">
                                    @if($reg->obligation)
                                        {{ $reg->obligation->status }} ({{ $reg->obligation->allocatedMoney()->format() }} / {{ $reg->obligation->dueMoney()->format() }})
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if($reg->status !== 'cancelled' && auth()->user()->can('members.update'))
                                        <form method="POST" action="{{ route('events.attendance', $reg) }}" class="inline-flex gap-1.5">
                                            @csrf
                                            <button type="submit" name="attended" value="1" class="rounded-lg bg-emerald-600 px-3 py-1 font-bold text-white hover:bg-emerald-700 cursor-pointer">Present</button>
                                            <button type="submit" name="attended" value="0" class="rounded-lg bg-slate-200 px-3 py-1 font-bold text-slate-700 hover:bg-slate-300 cursor-pointer">Absent</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-slate-400">No registrations yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="pyc-card p-5 flex items-center gap-4">
                <x-pyc-event-banner class="h-20 w-20" />
                <div class="min-w-0 text-xs space-y-1">
                    <span class="px-2.5 py-0.5 rounded-full font-bold capitalize {{ $event->status === 'cancelled' ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $event->status }}</span>
                    <p class="font-bold text-slate-900 truncate">{{ $event->venue }}</p>
                    <p class="text-slate-500">{{ $event->contributionCampaign ? 'Contribution: ' . $event->contributionCampaign->amountMoney()->format() : 'Free Parish Event' }}</p>
                </div>
            </div>

            @if($event->status !== 'cancelled')
                @can('notifications.send')
                    <div class="pyc-card p-5 space-y-3">
                        <h3 class="text-sm font-extrabold text-slate-900">Event Communications</h3>
                        <form method="POST" action="{{ route('events.announce', $event) }}">
                            @csrf
                            <button type="submit" class="btn-pyc-primary w-full text-xs">
                                Broadcast Event Announcement
                            </button>
                        </form>
                        <form method="POST" action="{{ route('events.remind', $event) }}">
                            @csrf
                            <button type="submit" class="btn-pyc-outline w-full text-xs">
                                Send Reminder to Registered Members
                            </button>
                        </form>
                    </div>

                    <div class="pyc-card p-5 space-y-2.5">
                        <h3 class="text-sm font-extrabold text-rose-800">Cancel Event</h3>
                        <form method="POST" action="{{ route('events.cancel', $event) }}" class="space-y-2.5">
                            @csrf
                            <input type="text" name="reason" required placeholder="Cancellation reason..." class="pyc-input text-xs py-2">
                            <button type="submit" class="w-full rounded-xl border border-rose-300 bg-rose-50 px-4 py-2 text-xs font-bold text-rose-700 hover:bg-rose-100 cursor-pointer">
                                Cancel Event &amp; Notify
                            </button>
                        </form>
                    </div>
                @endcan
            @endif
        </div>
    </div>
@endcomponent
