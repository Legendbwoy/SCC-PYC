@component('layouts.app', [
    'title' => 'Communications Hub',
    'heading' => 'SMS, Email & In-App Communications Hub',
    'subheading' => 'Provider-independent notification engine with independent channel tracking, template versioning, and exponential retry controls.',
])
    @include('portal.communications.partials.nav')

    <!-- 4 Pastel KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="pyc-kpi-blue">
            <p class="text-xs font-medium text-slate-500">SMS Deliveries</p>
            <p class="mt-1.5 text-2xl font-extrabold text-slate-900">{{ number_format($channelCounts['sms'] ?? 0) }}</p>
            <p class="mt-1 text-xs font-semibold {{ $smsEndpointConfigured ? 'text-emerald-700' : 'text-amber-700' }}">
                {{ $smsEndpointConfigured ? 'HTTP Gateway Active' : 'Sandbox / Awaiting Endpoint' }}
            </p>
        </div>

        <div class="pyc-kpi-green">
            <p class="text-xs font-medium text-slate-500">Email Deliveries</p>
            <p class="mt-1.5 text-2xl font-extrabold text-slate-900">{{ number_format($channelCounts['email'] ?? 0) }}</p>
            <p class="mt-1 text-xs font-semibold text-emerald-700">Mailer: {{ strtoupper((string) config('notifications.email.mailer', 'smtp')) }}</p>
        </div>

        <div class="pyc-kpi-purple">
            <p class="text-xs font-medium text-slate-500">Active Channel Templates</p>
            <p class="mt-1.5 text-2xl font-extrabold text-slate-900">{{ number_format($templateCount) }}</p>
            <p class="mt-1 text-xs font-semibold text-violet-700">18 official trigger codes versioned</p>
        </div>

        <div class="pyc-kpi-amber">
            <p class="text-xs font-medium text-slate-500">Failed / Retrying</p>
            <p class="mt-1.5 text-2xl font-extrabold {{ (($statusCounts['failed'] ?? 0) > 0) ? 'text-rose-700' : 'text-slate-900' }}">
                {{ number_format(($statusCounts['failed'] ?? 0) + ($statusCounts['retrying'] ?? 0)) }}
            </p>
            @if($totalCost !== null)
                <p class="mt-1 text-xs font-semibold text-amber-700">SMS Cost: {{ $totalCost->format() }}</p>
            @else
                <p class="mt-1 text-xs font-semibold text-amber-700">5-step exponential retry active</p>
            @endif
        </div>
    </div>

    <!-- Provider Status + Authorized Test Sender -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="pyc-card p-6 space-y-4">
            <h2 class="text-base font-extrabold text-slate-900">Channel &amp; Queue Configuration</h2>
            <dl class="space-y-2.5 text-xs">
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <dt class="text-slate-500">SMS Adapter Driver</dt>
                    <dd class="font-mono font-bold text-[#0F2D59]">{{ config('notifications.sms.driver') }} (Sender: {{ config('notifications.sms.generic_http.sender_id') }})</dd>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <dt class="text-slate-500">Email Transport</dt>
                    <dd class="font-mono font-bold text-[#0F2D59]">{{ config('notifications.email.mailer') }} ({{ config('mail.from.address') }})</dd>
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
            <div class="pyc-card p-6">
                <h2 class="text-base font-extrabold text-slate-900 mb-1">Authorized Connection &amp; Test-Send</h2>
                <p class="text-xs text-slate-500 mb-4">Verify SMS gateway or SMTP email delivery. Every test dispatch is audited.</p>

                <form method="POST" action="{{ route('communications.test') }}" class="space-y-3.5">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Channel</label>
                            <select name="channel" class="pyc-input py-2 bg-white">
                                <option value="email">Email (SMTP)</option>
                                <option value="sms">SMS (Generic HTTP)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Recipient Email or Phone</label>
                            <input type="text" name="destination" required placeholder="officer@stceciliapyc.org or +23324..." class="pyc-input py-2">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email Subject (optional for SMS)</label>
                        <input type="text" name="subject" value="SCC-PYC Notification Channel Test" class="pyc-input py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Test Message Body</label>
                        <textarea name="message" rows="2" required class="pyc-input py-2">This is an authorized St. Cecilia PYC notification channel verification test.</textarea>
                    </div>
                    <button type="submit" class="btn-pyc-primary py-2 px-5 text-xs">
                        Dispatch Test Notification
                    </button>
                </form>
            </div>
        @endif
    </div>
@endcomponent
