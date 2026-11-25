@component('layouts.app', [
    'title' => 'Parish Youth Events',
    'heading' => 'Parish Youth Events',
    'subheading' => 'Register for St. Cecilia Parish Youth Council retreats, fellowships, and outreach programs.',
])
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @forelse($events as $ev)
            @php
                $myReg = $ev->registrations->first();
                $isRegistered = $myReg && in_array($myReg->status, ['registered', 'attended'], true);
            @endphp
            <div class="pyc-card p-5 sm:p-6 flex flex-col sm:flex-row gap-5 items-start">
                <x-pyc-event-banner class="h-40 w-full sm:w-36" />

                <div class="flex-1 min-w-0 space-y-2.5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $isRegistered ? 'bg-emerald-100 text-emerald-800' : 'bg-emerald-50 text-emerald-700' }}">
                            {{ $isRegistered ? 'Registered (' . ucfirst($myReg->status) . ')' : 'Open for Registration' }}
                        </span>
                        <span class="text-xs font-mono font-bold text-[#1E549F]">
                            {{ $ev->contributionCampaign ? $ev->contributionCampaign->amountMoney()->format() : 'Free Entry' }}
                        </span>
                    </div>

                    <h2 class="text-lg font-extrabold text-slate-900">{{ $ev->title }}</h2>

                    <div class="space-y-1 text-xs text-slate-600">
                        <p class="font-semibold text-slate-800">
                            {{ $ev->starts_at->timezone('Africa/Accra')->format('D, M j, Y • g:i A') }}
                        </p>
                        <p>{{ $ev->venue }}</p>
                    </div>

                    @if($ev->description)
                        <p class="text-xs text-slate-500 leading-relaxed">{{ $ev->description }}</p>
                    @endif

                    <div class="pt-2 flex flex-wrap items-center gap-3">
                        @if($isRegistered)
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700">&check; Seat Confirmed</span>
                            @if($ev->allow_cancellation && $myReg->status === 'registered')
                                <form method="POST" action="{{ route('member.events.cancel', $ev) }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-rose-600 hover:underline cursor-pointer">
                                        Cancel Registration
                                    </button>
                                </form>
                            @endif
                        @elseif($ev->allow_registration && $member)
                            <form method="POST" action="{{ route('member.events.register', $ev) }}">
                                @csrf
                                <button type="submit" class="btn-pyc-green px-6 py-2">
                                    Register Now
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="lg:col-span-2 pyc-card p-10 text-center text-sm text-slate-400">
                No published events at this time.
            </div>
        @endforelse
    </div>
@endcomponent
