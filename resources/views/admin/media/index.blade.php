@component('layouts.app', [
    'title' => 'Media & Event Gallery',
    'heading' => 'Event Gallery & Image Management',
    'subheading' => 'Upload and curate parish youth event photo galleries, homepage featured moments, and official site branding images.',
])
    @slot('headerActions')
        <a href="{{ route('admin.executives.index') }}" class="btn-pyc-outline py-2 px-4 text-xs">
            Manage Know Your Executives (KYE) &rarr;
        </a>
    @endslot
    <!-- 4 Pastel KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="pyc-kpi-blue">
            <div class="h-10 w-10 rounded-xl bg-sky-100 text-blue-600 flex items-center justify-center mb-3">
                <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
            </div>
            <p class="text-xs font-medium text-slate-500">Total Gallery Photos</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format($stats['total_photos']) }}</p>
            <p class="mt-1 text-xs font-semibold text-blue-700">Events &amp; parish fellowship</p>
        </div>

        <div class="pyc-kpi-green">
            <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3">
                <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/></svg>
            </div>
            <p class="text-xs font-medium text-slate-500">Featured on Homepage</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format($stats['featured_photos']) }}</p>
            <p class="mt-1 text-xs font-semibold text-emerald-700">Highlighted on public site</p>
        </div>

        <div class="pyc-kpi-purple">
            <div class="h-10 w-10 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center mb-3">
                <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25"/></svg>
            </div>
            <p class="text-xs font-medium text-slate-500">Events with Galleries</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format($stats['events_with_photos']) }}</p>
            <p class="mt-1 text-xs font-semibold text-violet-700">Linked to PYC events</p>
        </div>

        <div class="pyc-kpi-amber">
            <div class="h-10 w-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center mb-3">
                <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42"/></svg>
            </div>
            <p class="text-xs font-medium text-slate-500">Custom Site Images</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $stats['configured_branding'] }} / 5</p>
            <p class="mt-1 text-xs font-semibold text-amber-700">Logo, hero &amp; backdrops</p>
        </div>
    </div>

    <!-- Upload Event & Parish Gallery Photos -->
    @if($canManageGallery)
        <div class="pyc-card p-6 sm:p-7 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">Upload Event &amp; Parish Gallery Photos</h2>
                    <p class="text-xs text-slate-500">Select up to 12 JPG, PNG, or WEBP photos (max 5 MB each). Link them to a specific PYC event or publish to the general parish youth gallery.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.media.gallery.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Select Photo(s) *</label>
                    <input type="file" name="images[]" multiple required accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                           class="pyc-input py-2 text-xs file:mr-3 file:rounded-lg file:border-0 file:bg-[#0F2D59] file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-[#16407B]">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Linked PYC Event (Optional)</label>
                    <select name="event_id" class="pyc-input py-2.5 bg-white">
                        <option value="">— General Parish Youth Gallery —</option>
                        @foreach($events as $ev)
                            <option value="{{ $ev->id }}" @selected((string) old('event_id', $filters['event_id']) === (string) $ev->id)>
                                {{ $ev->title }} ({{ $ev->starts_at->format('M Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Album / Photo Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. Lenten Youth Retreat Worship"
                           class="pyc-input">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Caption / Description (Optional)</label>
                    <input type="text" name="caption" value="{{ old('caption') }}" placeholder="Brief description shown in the event & homepage gallery..."
                           class="pyc-input">
                </div>

                <div class="flex flex-col justify-center gap-2 pt-1">
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_public" value="1" checked class="h-4 w-4 rounded border-slate-300 text-[#0F2D59]">
                        <span>Visible on Public Website &amp; Member Portal</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" class="h-4 w-4 rounded border-slate-300 text-[#0F2D59]">
                        <span>Feature on Public Homepage</span>
                    </label>
                </div>

                <div class="sm:col-span-3 flex justify-end">
                    <button type="submit" class="btn-pyc-primary px-6">
                        Upload Photo(s) to Gallery
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Gallery Filter Bar & Photo Grid -->
    <div class="pyc-card p-6 space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <h2 class="text-lg font-extrabold text-slate-900">Event &amp; Fellowship Photo Library</h2>

            <form method="GET" action="{{ route('admin.media.index') }}" class="flex flex-wrap items-center gap-2.5">
                <select name="event_id" class="pyc-input w-auto py-2 text-xs bg-white">
                    <option value="">All Events &amp; Collections</option>
                    <option value="general" @selected($filters['event_id'] === 'general')>General Parish Youth Gallery</option>
                    @foreach($events as $ev)
                        <option value="{{ $ev->id }}" @selected($filters['event_id'] === (string) $ev->id)>
                            {{ $ev->title }}
                        </option>
                    @endforeach
                </select>

                <select name="visibility" class="pyc-input w-auto py-2 text-xs bg-white">
                    <option value="">All Visibility</option>
                    <option value="featured" @selected($filters['visibility'] === 'featured')>Featured on Homepage</option>
                    <option value="public" @selected($filters['visibility'] === 'public')>Public</option>
                    <option value="private" @selected($filters['visibility'] === 'private')>Internal / Portal Only</option>
                </select>

                <button type="submit" class="btn-pyc-primary py-2 px-4 text-xs">Filter</button>
                @if($filters['event_id'] !== '' || $filters['visibility'] !== '')
                    <a href="{{ route('admin.media.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">Reset</a>
                @endif
            </form>
        </div>

        @if($assets->isEmpty())
            <div class="py-12 text-center space-y-2">
                <p class="text-sm font-semibold text-slate-600">No gallery photos found for the selected filter.</p>
                <p class="text-xs text-slate-400">Upload event moments or parish fellowship photos using the form above.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach($assets as $asset)
                    <div class="rounded-2xl border border-slate-200/80 bg-white overflow-hidden shadow-2xs flex flex-col justify-between">
                        <div>
                            <button type="button"
                                    onclick="const m = document.getElementById('pycLightboxModal'); document.getElementById('pycLightboxImg').src = @js($asset->url()); document.getElementById('pycLightboxCaption').textContent = @js(($asset->title ?: ($asset->event?->title ?: 'PYC Gallery')) . ($asset->caption ? ' — ' . $asset->caption : '')); m.classList.remove('hidden');"
                                    class="relative block w-full aspect-[4/3] bg-slate-100 overflow-hidden group cursor-pointer">
                                <img src="{{ $asset->url() }}"
                                     alt="{{ $asset->title ?? $asset->caption ?? 'PYC Gallery Photo' }}"
                                     class="h-full w-full object-cover group-hover:scale-105 transition duration-300">
                                <div class="absolute top-2.5 left-2.5 flex flex-wrap gap-1">
                                    @if($asset->is_featured)
                                        <span class="px-2 py-0.5 rounded-full bg-[#F5C542] text-[#0B2545] text-[10px] font-extrabold shadow-xs">Featured</span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold shadow-xs {{ $asset->is_public ? 'bg-emerald-600 text-white' : 'bg-slate-800/80 text-white' }}">
                                        {{ $asset->is_public ? 'Public' : 'Internal' }}
                                    </span>
                                </div>
                            </button>

                            <div class="p-3.5 space-y-1">
                                <p class="text-xs font-bold text-slate-900 truncate">
                                    {{ $asset->title ?: ($asset->event?->title ?: 'Parish Youth Fellowship') }}
                                </p>
                                @if($asset->event)
                                    <a href="{{ route('events.show', $asset->event) }}" class="block text-[11px] font-semibold text-[#1E549F] hover:underline truncate">
                                        Event: {{ $asset->event->title }}
                                    </a>
                                @else
                                    <p class="text-[11px] text-slate-400">General Parish Gallery</p>
                                @endif
                                @if($asset->caption)
                                    <p class="text-[11px] text-slate-600 line-clamp-2">{{ $asset->caption }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="px-3.5 py-2.5 bg-slate-50/80 border-t border-slate-100 space-y-2 text-[11px]">
                            <div class="flex items-center justify-between text-slate-400">
                                <span>{{ $asset->formattedSize() }}</span>
                                <span>{{ $asset->created_at?->format('d M Y') }}</span>
                            </div>

                            @if($canManageGallery)
                                <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-200/60">
                                    <details class="relative">
                                        <summary class="cursor-pointer font-bold text-[#1E549F] hover:underline">Edit Details</summary>
                                        <form method="POST" action="{{ route('admin.media.gallery.update', $asset) }}" class="mt-2 p-3 rounded-xl bg-white border border-slate-200 shadow-lg space-y-2 w-60 z-20">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="title" value="{{ $asset->title }}" placeholder="Title" class="pyc-input py-1.5 text-xs">
                                            <input type="text" name="caption" value="{{ $asset->caption }}" placeholder="Caption" class="pyc-input py-1.5 text-xs">
                                            <select name="event_id" class="pyc-input py-1.5 text-xs bg-white">
                                                <option value="">General Gallery</option>
                                                @foreach($events as $ev)
                                                    <option value="{{ $ev->id }}" @selected($asset->event_id === $ev->id)>{{ $ev->title }}</option>
                                                @endforeach
                                            </select>
                                            <label class="flex items-center gap-1.5 text-xs text-slate-700">
                                                <input type="checkbox" name="is_public" value="1" @checked($asset->is_public) class="rounded border-slate-300 text-[#0F2D59]">
                                                Public
                                            </label>
                                            <label class="flex items-center gap-1.5 text-xs text-slate-700">
                                                <input type="checkbox" name="is_featured" value="1" @checked($asset->is_featured) class="rounded border-slate-300 text-[#0F2D59]">
                                                Featured
                                            </label>
                                            <button type="submit" class="btn-pyc-primary w-full py-1.5 text-xs">Save</button>
                                        </form>
                                    </details>

                                    <form method="POST" action="{{ route('admin.media.gallery.destroy', $asset) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-bold text-rose-600 hover:underline cursor-pointer">Delete</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pt-2">
                {{ $assets->links() }}
            </div>
        @endif
    </div>

    <!-- Site, Hero & Branding Image Management Slots -->
    <div class="pyc-card p-6 sm:p-7 space-y-5">
        <div>
            <h2 class="text-lg font-extrabold text-slate-900">Site, Hero &amp; Branding Image Slots</h2>
            <p class="text-xs text-slate-500">
                Customize the official PYC logo crest, favicon, homepage hero banner, login backdrop, and default event cover image. Removing any custom image cleanly restores the built-in PYC vector artwork.
            </p>
        </div>

        @php
            $slotUrls = [
                'logo' => $branding['logo_url'] ?? null,
                'favicon' => $branding['favicon_url'] ?? null,
                'hero_image' => $branding['hero_image_url'] ?? null,
                'login_backdrop' => $branding['login_bg_url'] ?? null,
                'default_event_banner' => $branding['default_event_banner_url'] ?? null,
            ];
            $slotHints = [
                'logo' => 'Displayed in portal sidebar, public header, login card & receipts (PNG/WEBP with transparent background recommended).',
                'favicon' => 'Browser tab icon across public website & member/executive portals (square PNG/WEBP recommended).',
                'hero_image' => 'Featured parish youth council photo on the public homepage hero card (landscape 4:3 or 16:9 recommended).',
                'login_backdrop' => 'Atmospheric background image behind the Sign-In card on /login.',
                'default_event_banner' => 'Fallback cover image for PYC events that do not have an individual event flyer uploaded.',
            ];
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($brandingSlots as $slotKey => $slotMeta)
                @php
                    $currentUrl = $slotUrls[$slotKey] ?? null;
                @endphp
                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4 flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-extrabold text-slate-900">{{ $slotMeta['label'] }}</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $currentUrl ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                {{ $currentUrl ? 'Custom Uploaded' : 'Built-in Default' }}
                            </span>
                        </div>

                        <div class="h-32 rounded-xl border border-slate-200 bg-white flex items-center justify-center overflow-hidden p-2">
                            @if($currentUrl)
                                <img src="{{ $currentUrl }}" alt="{{ $slotMeta['label'] }}" class="max-h-full max-w-full object-contain rounded-lg">
                            @elseif($slotKey === 'logo' || $slotKey === 'favicon')
                                <x-pyc-logo variant="inline-light" size="sm" />
                            @else
                                <x-pyc-event-banner class="h-full w-full" />
                            @endif
                        </div>

                        <p class="text-[11px] text-slate-500 leading-relaxed">{{ $slotHints[$slotKey] ?? '' }}</p>
                    </div>

                    @if($canManageBranding)
                        <div class="space-y-2 pt-2 border-t border-slate-200/70">
                            <form method="POST" action="{{ route('admin.media.branding.update', $slotKey) }}" enctype="multipart/form-data" class="space-y-2">
                                @csrf
                                <input type="file" name="image" required accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                       class="block w-full text-xs text-slate-600 file:mr-2.5 file:rounded-lg file:border-0 file:bg-[#0F2D59] file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-[#16407B]">
                                <button type="submit" class="btn-pyc-primary w-full py-2 text-xs">
                                    {{ $currentUrl ? 'Replace Image' : 'Upload Image' }}
                                </button>
                            </form>

                            @if($currentUrl)
                                <form method="POST" action="{{ route('admin.media.branding.destroy', $slotKey) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full rounded-xl border border-rose-200 bg-white py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50 cursor-pointer">
                                        Reset to Default
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Lightbox Preview Modal -->
    <div id="pycLightboxModal"
         onclick="if (event.target === this) this.classList.add('hidden');"
         class="hidden fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-xs p-4 flex items-center justify-center">
        <div class="max-w-4xl w-full bg-white rounded-3xl overflow-hidden shadow-2xl">
            <div class="bg-slate-950 flex items-center justify-center max-h-[75vh] overflow-hidden">
                <img id="pycLightboxImg" src="" alt="Gallery Preview" class="max-h-[75vh] w-auto object-contain">
            </div>
            <div class="px-6 py-4 flex items-center justify-between gap-4">
                <p id="pycLightboxCaption" class="text-sm font-bold text-slate-900"></p>
                <button type="button"
                        onclick="document.getElementById('pycLightboxModal').classList.add('hidden');"
                        class="btn-pyc-outline py-1.5 px-4 text-xs">
                    Close
                </button>
            </div>
        </div>
    </div>
@endcomponent
