@php
    $canManageEventMedia = auth()->user()->can('notifications.send') || auth()->user()->can('settings.manage');
@endphp

@component('layouts.app', [
    'title' => $event->title,
    'heading' => $event->title . " ({$event->reference})",
    'subheading' => $event->starts_at->timezone('Africa/Accra')->format('D, d M Y • H:i') . ' at ' . $event->venue,
])
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Registrations & Attendance -->
            <div class="pyc-card overflow-hidden">
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

            <!-- Event Photo Gallery Section -->
            <div class="pyc-card p-6 space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Event Photo Gallery ({{ $event->galleryPhotos->count() }})</h2>
                        <p class="text-xs text-slate-500">Photos uploaded here appear in this event's album and (when marked public) in the Member Portal &amp; Homepage gallery.</p>
                    </div>
                    <a href="{{ route('admin.media.index', ['event_id' => $event->id]) }}" class="text-xs font-bold text-[#1E549F] hover:underline">
                        Open in Media Library &rarr;
                    </a>
                </div>

                @if($canManageEventMedia)
                    <form method="POST" action="{{ route('admin.media.gallery.store') }}" enctype="multipart/form-data" class="p-4 rounded-2xl bg-slate-50/80 border border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @csrf
                        <input type="hidden" name="event_id" value="{{ $event->id }}">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Select Event Photo(s) *</label>
                            <input type="file" name="images[]" multiple required accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                   class="block w-full text-xs text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-[#0F2D59] file:px-2.5 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Caption (Optional)</label>
                            <input type="text" name="caption" placeholder="e.g. Opening prayer & worship" class="pyc-input py-1.5 text-xs bg-white">
                        </div>
                        <div class="flex items-end justify-between gap-2">
                            <div class="space-y-1">
                                <label class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-700">
                                    <input type="checkbox" name="is_public" value="1" checked class="rounded border-slate-300 text-[#0F2D59]">
                                    Public
                                </label>
                                <label class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-700">
                                    <input type="checkbox" name="is_featured" value="1" class="rounded border-slate-300 text-[#0F2D59]">
                                    Feature
                                </label>
                            </div>
                            <button type="submit" class="btn-pyc-primary py-2 px-4 text-xs">Upload</button>
                        </div>
                    </form>
                @endif

                @if($event->galleryPhotos->isEmpty())
                    <p class="py-6 text-center text-xs text-slate-400">No photos uploaded to this event gallery yet.</p>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach($event->galleryPhotos as $photo)
                            <div class="rounded-2xl border border-slate-200/80 overflow-hidden bg-white shadow-2xs">
                                <a href="{{ $photo->url() }}" target="_blank" rel="noopener" class="block aspect-[4/3] bg-slate-100 overflow-hidden">
                                    <img src="{{ $photo->url() }}" alt="{{ $photo->caption ?? $event->title }}" class="h-full w-full object-cover hover:scale-105 transition">
                                </a>
                                <div class="p-2.5 flex items-center justify-between gap-2 text-[11px]">
                                    <span class="text-slate-600 truncate">{{ $photo->caption ?: ($photo->title ?: 'Event Photo') }}</span>
                                    @if($canManageEventMedia)
                                        <form method="POST" action="{{ route('admin.media.gallery.destroy', $photo) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-bold text-rose-600 hover:underline cursor-pointer">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <!-- Event Cover Flyer Card -->
            <div class="pyc-card p-5 space-y-3.5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-extrabold text-slate-900">Event Cover Flyer</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold capitalize {{ $event->status === 'cancelled' ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $event->status }}</span>
                </div>

                <x-pyc-event-banner :src="$event->bannerUrl()" :alt="$event->title" class="h-44 w-full" />

                <p class="text-xs text-slate-500">
                    {{ $event->contributionCampaign ? 'Contribution: ' . $event->contributionCampaign->amountMoney()->format() : 'Free Parish Event' }}
                </p>

                @if($canManageEventMedia)
                    <form method="POST" action="{{ route('events.banner.update', $event) }}" enctype="multipart/form-data" class="space-y-2 pt-2 border-t border-slate-100">
                        @csrf
                        <label class="block text-[11px] font-semibold text-slate-700">Upload / Replace Event Flyer</label>
                        <input type="file" name="banner_image" required accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                               class="block w-full text-xs text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-[#0F2D59] file:px-2.5 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                        <button type="submit" class="btn-pyc-primary w-full py-2 text-xs">
                            Save Cover Flyer
                        </button>
                    </form>

                    @if($event->banner_image_path)
                        <form method="POST" action="{{ route('events.banner.destroy', $event) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full rounded-xl border border-rose-200 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50 cursor-pointer">
                                Remove Custom Flyer
                            </button>
                        </form>
                    @endif
                @endif
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
