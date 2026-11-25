@component('layouts.app', [
    'title' => $event->title,
    'heading' => $event->title . " ({$event->reference})",
    'subheading' => $event->starts_at->timezone('Africa/Accra')->format('d M Y, H:i') . ' at ' . $event->venue,
])
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-base font-bold text-slate-900">Registrations &amp; Attendance ({{ $event->registrations->count() }})</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">Member</th>
                            <th class="px-5 py-3 text-left font-semibold">Status</th>
                            <th class="px-5 py-3 text-left font-semibold">Contribution Obligation</th>
                            <th class="px-5 py-3 text-right font-semibold">Mark Attendance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($event->registrations as $reg)
                            <tr>
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-slate-900">{{ $reg->member?->fullName() }}</p>
                                    <p class="font-mono text-teal-800">{{ $reg->member?->member_number }}</p>
                                </td>
                                <td class="px-5 py-3 capitalize font-semibold">{{ $reg->status }}</td>
                                <td class="px-5 py-3 font-mono">
                                    @if($reg->obligation)
                                        {{ $reg->obligation->status }} ({{ $reg->obligation->allocatedMoney()->format() }} / {{ $reg->obligation->dueMoney()->format() }})
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if($reg->status !== 'cancelled' && auth()->user()->can('members.update'))
                                        <form method="POST" action="{{ route('events.attendance', $reg) }}" class="inline-flex gap-1">
                                            @csrf
                                            <button type="submit" name="attended" value="1" class="rounded bg-emerald-700 px-2.5 py-1 font-semibold text-white cursor-pointer">Present</button>
                                            <button type="submit" name="attended" value="0" class="rounded bg-slate-200 px-2.5 py-1 font-semibold text-slate-700 cursor-pointer">Absent</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-slate-400">No registrations yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            @if($event->status !== 'cancelled')
                @can('notifications.send')
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-3">
                        <h3 class="text-sm font-bold text-slate-900">Event Communications</h3>
                        <form method="POST" action="{{ route('events.announce', $event) }}">
                            @csrf
                            <button type="submit" class="w-full rounded-xl px-4 py-2 text-xs font-semibold text-white cursor-pointer" style="background-color: var(--pyc-primary);">
                                Broadcast Event Announcement
                            </button>
                        </form>
                        <form method="POST" action="{{ route('events.remind', $event) }}">
                            @csrf
                            <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white cursor-pointer">
                                Send Reminder to Registered Members
                            </button>
                        </form>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-2.5">
                        <h3 class="text-sm font-bold text-rose-800">Cancel Event</h3>
                        <form method="POST" action="{{ route('events.cancel', $event) }}" class="space-y-2">
                            @csrf
                            <input type="text" name="reason" required placeholder="Cancellation reason..."
                                   class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            <button type="submit" class="w-full rounded-xl border border-rose-300 bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-700 cursor-pointer">
                                Cancel Event &amp; Notify
                            </button>
                        </form>
                    </div>
                @endcan
            @endif
        </div>
    </div>
@endcomponent
