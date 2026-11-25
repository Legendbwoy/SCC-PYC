@component('layouts.app', [
    'title' => 'Communications Hub',
    'heading' => 'SMS, Email & In-App Communications Hub',
    'subheading' => 'Provider-independent notification engine with independent channel tracking, template versioning, and exponential retry controls.',
])
    @include('portal.communications.partials.nav')

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">SMS Deliveries</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($channelCounts['sms'] ?? 0) }}</p>
            <p class="mt-1 text-xs {{ $smsEndpointConfigured ? 'text-emerald-700' : 'text-amber-700' }}">
                Generic HTTP: {{ $smsEndpointConfigured ? 'Endpoint Configured' : 'Sandbox / Awaiting Endpoint' }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Email Deliveries</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($channelCounts['email'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-slate-500">Mailer: {{ strtoupper((string) config('notifications.email.mailer', 'smtp')) }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Channel Templates</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($templateCount) }}</p>
            <p class="mt-1 text-xs text-slate-500">18 official trigger codes versioned</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Failed / Retrying</p>
            <p class="mt-2 text-2xl font-bold {{ (($statusCounts['failed'] ?? 0) > 0) ? 'text-rose-700' : 'text-slate-900' }}">
                {{ number_format(($statusCounts['failed'] ?? 0) + ($statusCounts['retrying'] ?? 0)) }}
            </p>
            @if($totalCost !== null)
                <p class="mt-1 text-xs text-slate-500">Recorded SMS Cost: {{ $totalCost->format() }}</p>
            @else
                <p class="mt-1 text-xs text-slate-500">5-step exponential retry active</p>
            @endif
        </div>
    </div>

    <!-- Provider Status + Authorized Test Sender -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4">
            <h2 class="text-base font-bold text-slate-900">Channel &amp; Queue Configuration</h2>
            <dl class="space-y-2.5 text-xs">
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <dt class="text-slate-500">SMS Adapter Driver</dt>
                    <dd class="font-mono font-semibold text-slate-800">{{ config('notifications.sms.driver') }} (Sender: {{ config('notifications.sms.generic_http.sender_id') }})</dd>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <dt class="text-slate-500">Email Transport</dt>
                    <dd class="font-mono font-semibold text-slate-800">{{ config('notifications.email.mailer') }} ({{ config('mail.from.address') }})</dd>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <dt class="text-slate-500">Priority Queues</dt>
                    <dd class="font-mono text-slate-700">high_priority, notifications, bulk_notifications</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-slate-500">Retry Backoff Schedule</dt>
                    <dd class="font-mono text-slate-700">1m &rarr; 5m &rarr; 15m &rarr; 1h &rarr; 3h</dd>
                </div>
            </dl>
        </div>

        @if(auth()->user()->can('notifications.manage_providers') || auth()->user()->can('notifications.send'))
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
                <h2 class="text-base font-bold text-slate-900 mb-1">Authorized Connection &amp; Test-Send</h2>
                <p class="text-xs text-slate-500 mb-4">Verify SMS gateway or SMTP email delivery. Every test dispatch is audited.</p>

                <form method="POST" action="{{ route('communications.test') }}" class="space-y-3.5">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Channel</label>
                            <select name="channel" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white">
                                <option value="email">Email (SMTP)</option>
                                <option value="sms">SMS (Generic HTTP)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Recipient Email or Phone</label>
                            <input type="text" name="destination" required placeholder="officer@stceciliapyc.org or +23324..."
                                   class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Email Subject (optional for SMS)</label>
                        <input type="text" name="subject" value="SCC-PYC Notification Channel Test"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Test Message Body</label>
                        <textarea name="message" rows="2" required
                                  class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">This is an authorized St. Cecilia PYC notification channel verification test.</textarea>
                    </div>
                    <button type="submit"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-white cursor-pointer"
                            style="background-color: var(--pyc-primary);">
                        Dispatch Test Notification
                    </button>
                </form>
            </div>
        @endif
    </div>
@endcomponent
