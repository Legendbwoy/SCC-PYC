@component('layouts.app', [
    'title' => 'Know Your Executives (KYE)',
    'heading' => 'Know Your Executives (KYE)',
    'subheading' => 'Manage the Parish Youth Council leadership roster, official positions, display order, and portrait photos shown on the public landing page.',
])
    @slot('headerActions')
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.media.index') }}" class="btn-pyc-outline py-2 px-4 text-xs">
                Media &amp; Event Gallery
            </a>
            <a href="{{ route('home') }}#executives" target="_blank" rel="noopener" class="btn-pyc-primary py-2 px-4 text-xs">
                Preview on Landing Page &nearr;
            </a>
        </div>
    @endslot

    <!-- 4 Pastel KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="pyc-kpi-blue">
            <p class="text-xs font-medium text-slate-500">Total Listed Executives</p>
            <p class="mt-1.5 text-2xl font-extrabold text-slate-900">{{ number_format($stats['total']) }}</p>
            <p class="mt-1 text-xs font-semibold text-blue-700">Chaplains &amp; council officers</p>
        </div>

        <div class="pyc-kpi-green">
            <p class="text-xs font-medium text-slate-500">Visible on Landing Page</p>
            <p class="mt-1.5 text-2xl font-extrabold text-emerald-700">{{ number_format($stats['active']) }}</p>
            <p class="mt-1 text-xs font-semibold text-emerald-700">Published in KYE showcase</p>
        </div>

        <div class="pyc-kpi-purple">
            <p class="text-xs font-medium text-slate-500">Profiles with Portrait Photos</p>
            <p class="mt-1.5 text-2xl font-extrabold text-slate-900">{{ number_format($stats['with_photos']) }}</p>
            <p class="mt-1 text-xs font-semibold text-violet-700">Initials crest shown if blank</p>
        </div>

        <div class="pyc-kpi-amber">
            <p class="text-xs font-medium text-slate-500">Hidden / Past Profiles</p>
            <p class="mt-1.5 text-2xl font-extrabold text-slate-900">{{ number_format($stats['hidden']) }}</p>
            <p class="mt-1 text-xs font-semibold text-amber-700">Preserved off homepage</p>
        </div>
    </div>

    <!-- Add New Executive Form -->
    @if($canManageExecutives)
        <div class="pyc-card p-6 sm:p-7 space-y-4">
            <div>
                <h2 class="text-lg font-extrabold text-slate-900">Add Executive / Chaplain to KYE</h2>
                <p class="text-xs text-slate-500">Upload a square or portrait photo (JPG, PNG, or WEBP up to 5 MB) and set a display order (lower numbers appear first on the homepage).</p>
            </div>

            <form method="POST" action="{{ route('admin.executives.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name *</label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" required placeholder="e.g. Rev. Fr. Anthony Mensah"
                           class="pyc-input">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Official Position / Portfolio *</label>
                    <input type="text" name="position" value="{{ old('position') }}" required placeholder="e.g. PYC President"
                           class="pyc-input">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tenure</label>
                        <input type="text" name="tenure" value="{{ old('tenure', now()->year . ' – ' . (now()->year + 2)) }}" placeholder="2026 – 2028"
                               class="pyc-input">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Order #</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $nextSortOrder) }}" min="0" max="999"
                               class="pyc-input font-mono">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Short Bio / Ministry Note (Optional)</label>
                    <input type="text" name="bio" value="{{ old('bio') }}" placeholder="e.g. Guiding St. Cecilia youth in faith, liturgy, and parish service."
                           class="pyc-input">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Portrait Photo (Optional)</label>
                    <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                           class="pyc-input py-2 text-xs file:mr-2.5 file:rounded-lg file:border-0 file:bg-[#0F2D59] file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-[#16407B]">
                </div>

                <div class="sm:col-span-3 flex flex-wrap items-center justify-between gap-3 pt-1">
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-slate-300 text-[#0F2D59]">
                        <span>Display on Public Landing Page ("Know Your Executives")</span>
                    </label>

                    <button type="submit" class="btn-pyc-primary px-6">
                        Add Executive Profile
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Listed Executives Roster Grid -->
    <div class="pyc-card p-6 space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <h2 class="text-lg font-extrabold text-slate-900">Listed Council Executives ({{ $executives->count() }})</h2>
            <span class="text-xs text-slate-500">Ordered by <code class="font-mono font-semibold text-[#0F2D59]">sort_order</code></span>
        </div>

        @if($executives->isEmpty())
            <div class="py-12 text-center space-y-2">
                <p class="text-sm font-semibold text-slate-600">No executive profiles have been added yet.</p>
                <p class="text-xs text-slate-400">Add your Youth Chaplain, PYC President, and council officers above to showcase them on the landing page.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @foreach($executives as $exec)
                    <div class="rounded-2xl border border-slate-200/80 bg-white overflow-hidden shadow-2xs flex flex-col justify-between">
                        <div>
                            <!-- Portrait Frame -->
                            <div class="relative aspect-[4/4] bg-gradient-to-br from-[#081F3F] via-[#0F2D59] to-[#1D56A6] flex items-center justify-center overflow-hidden">
                                @if($exec->photoUrl())
                                    <img src="{{ $exec->photoUrl() }}" alt="{{ $exec->full_name }}" class="h-full w-full object-cover">
                                @else
                                    <div class="text-center select-none space-y-2">
                                        <div class="mx-auto h-20 w-20 rounded-full bg-white/15 ring-2 ring-[#F5C542]/70 flex items-center justify-center text-2xl font-extrabold text-white">
                                            {{ $exec->initials() }}
                                        </div>
                                        <p class="text-[11px] font-semibold text-sky-200/80">PYC Leadership</p>
                                    </div>
                                @endif

                                <div class="absolute top-2.5 left-2.5 right-2.5 flex items-center justify-between gap-2">
                                    <span class="px-2 py-0.5 rounded-full bg-slate-950/70 text-white font-mono text-[10px] font-bold">
                                        #{{ $exec->sort_order }}
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold shadow-xs {{ $exec->is_active ? 'bg-emerald-600 text-white' : 'bg-slate-700 text-slate-200' }}">
                                        {{ $exec->is_active ? 'Visible' : 'Hidden' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Name, Position & Bio -->
                            <div class="p-4 space-y-1.5">
                                <span class="inline-block px-2.5 py-0.5 rounded-full bg-amber-50 border border-amber-200/70 text-[#0B2545] text-[11px] font-extrabold">
                                    {{ $exec->position }}
                                </span>
                                <h3 class="text-base font-extrabold text-slate-900 leading-snug">{{ $exec->full_name }}</h3>
                                @if($exec->tenure)
                                    <p class="text-xs font-semibold text-[#1E549F]">{{ $exec->tenure }}</p>
                                @endif
                                @if($exec->bio)
                                    <p class="text-xs text-slate-500 leading-relaxed line-clamp-2 pt-0.5">{{ $exec->bio }}</p>
                                @endif
                            </div>
                        </div>

                        @if($canManageExecutives)
                            <div class="px-4 py-3 bg-slate-50/80 border-t border-slate-100 space-y-2 text-xs">
                                <details>
                                    <summary class="cursor-pointer font-bold text-[#1E549F] hover:underline">Edit Profile / Photo</summary>
                                    <form method="POST" action="{{ route('admin.executives.update', $exec) }}" enctype="multipart/form-data" class="mt-3 space-y-2.5 pt-2.5 border-t border-slate-200/70">
                                        @csrf
                                        @method('PATCH')
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600">Full Name</label>
                                            <input type="text" name="full_name" value="{{ $exec->full_name }}" required class="pyc-input py-1.5 text-xs bg-white">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600">Position</label>
                                            <input type="text" name="position" value="{{ $exec->position }}" required class="pyc-input py-1.5 text-xs bg-white">
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600">Tenure</label>
                                                <input type="text" name="tenure" value="{{ $exec->tenure }}" class="pyc-input py-1.5 text-xs bg-white">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600">Order #</label>
                                                <input type="number" name="sort_order" value="{{ $exec->sort_order }}" min="0" max="999" class="pyc-input py-1.5 text-xs font-mono bg-white">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600">Short Bio</label>
                                            <input type="text" name="bio" value="{{ $exec->bio }}" class="pyc-input py-1.5 text-xs bg-white">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600">Replace Portrait Photo</label>
                                            <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                                   class="block w-full text-[11px] text-slate-600 file:mr-2 file:rounded-md file:border-0 file:bg-[#0F2D59] file:px-2.5 file:py-1 file:text-[11px] file:font-semibold file:text-white">
                                        </div>
                                        <label class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-700">
                                            <input type="checkbox" name="is_active" value="1" @checked($exec->is_active) class="rounded border-slate-300 text-[#0F2D59]">
                                            Visible on Landing Page
                                        </label>
                                        <button type="submit" class="btn-pyc-primary w-full py-1.5 text-xs">Save Changes</button>
                                    </form>
                                </details>

                                <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 text-[11px]">
                                    @if($exec->photo_path)
                                        <form method="POST" action="{{ route('admin.executives.photo.destroy', $exec) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-semibold text-amber-700 hover:underline cursor-pointer">Remove Photo</button>
                                        </form>
                                    @else
                                        <span class="text-slate-400">Default avatar</span>
                                    @endif

                                    <form method="POST" action="{{ route('admin.executives.destroy', $exec) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-bold text-rose-600 hover:underline cursor-pointer">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endcomponent
