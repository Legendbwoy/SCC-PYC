@component('layouts.app', [
    'title' => 'Parish Youth Events',
    'heading' => 'Upcoming Parish Youth Events',
    'subheading' => 'Register for St. Cecilia Parish Youth Council programs, retreats, and fellowships.',
])
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($events as $ev)
            @php
                $myReg = $ev->registrations->first();
                $isRegistered = $myReg && in_array($myReg->status, ['registered', 'attended']);
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs flex flex-col justify-between space-y-4">
                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono text-xs text-teal-800 font-semibold">{{ $ev->reference }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $ev->contributionCampaign ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-700' }}">
                            {{ $ev->contributionCampaign ? 'Contribution: '.$ev->contributionCampaign->amountMoney()->format() : 'Free Entry' }}
                        </span>
                    </div>
                    <h2 class="text-lg font-bold text-slate-900">{{ $ev->title }}</h2>
                    <p class="text-xs text-slate-600">
                        <strong>When:</strong> {{ $ev->starts_at->timezone('Africa/Accra')->format('d M Y, H:i') }} &bull;
                        <strong>Venue:</strong> {{ $ev->venue }}
                    </p>
                    @if($ev->description)
                        <p class="text-sm text-slate-600 pt-1">{{ $ev->description }}</p>
                    @endif
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    @if($isRegistered)
                        <span class="text-xs font-bold text-emerald-700">Registered ({{ ucfirst($myReg->status) }})</span>
                        @if($ev->allow_cancellation && $myReg->status === 'registered')
                            <form method="POST" action="{{ route('member.events.cancel', $ev) }}">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-rose-700 hover:underline cursor-pointer">Cancel Registration</button>
                            </form>
                        @endif
                    @elseif($ev->allow_registration && $member)
                        <form method="POST" action="{{ route('member.events.register', $ev) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full rounded-xl px-4 py-2 text-xs font-semibold text-white cursor-pointer" style="background-color: var(--pyc-primary);">
                                Register for Event
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200 p-10 text-center text-sm text-slate-400">
                No published events at this time.
            </div>
        @endforelse
    </div>
@endcomponent
