@component('layouts.app', [
    'title' => 'Organization & Branding',
    'heading' => 'Organization & Configurable Branding Settings',
    'subheading' => 'Manage non-secret identity and parish branding settings (Spec Section 18.1 & 18.3).',
])
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <h2 class="text-base font-bold text-slate-900 mb-4">Parish &amp; Youth Council Identity</h2>

            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700">Official Organization Name</label>
                        <input type="text" name="organization_name" value="{{ old('organization_name', $branding['organization_name']) }}" required
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Short Name / Acronym</label>
                        <input type="text" name="short_name" value="{{ old('short_name', $branding['short_name']) }}" required
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Parish Name</label>
                        <input type="text" name="parish_name" value="{{ old('parish_name', $branding['parish_name']) }}" required
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700">Motto / Tagline</label>
                        <input type="text" name="tagline" value="{{ old('tagline', $branding['tagline']) }}"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Official Contact Email</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $branding['contact_email']) }}"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Official Contact Phone</label>
                        <input type="text" name="contact_phone" value="{{ old('contact_phone', $branding['contact_phone']) }}"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700">Parish Address</label>
                        <input type="text" name="address" value="{{ old('address', $branding['address']) }}"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Primary Brand Colour (Hex)</label>
                        <input type="text" name="primary_color" value="{{ old('primary_color', $branding['primary_color']) }}" required
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Accent Brand Colour (Hex)</label>
                        <input type="text" name="accent_color" value="{{ old('accent_color', $branding['accent_color']) }}" required
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="rounded-xl px-5 py-2.5 text-sm font-semibold text-white cursor-pointer"
                            style="background-color: var(--pyc-primary);">
                        Save Organization Settings
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4 h-fit">
            <h2 class="text-base font-bold text-slate-900">Protected Core Parameters</h2>
            <p class="text-xs text-slate-500 leading-relaxed">
                Financial governance parameters and provider secrets are isolated in environment configuration and cannot be weakened via UI settings.
            </p>
            <dl class="space-y-2.5 text-xs">
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <dt class="text-slate-500">Currency / Timezone</dt>
                    <dd class="font-semibold text-slate-800">{{ config('pyc.organization.currency') }} / {{ config('pyc.organization.timezone') }}</dd>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <dt class="text-slate-500">Reference Prefix</dt>
                    <dd class="font-mono font-semibold text-slate-800">{{ config('pyc.organization.reference_prefix') }}-*</dd>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <dt class="text-slate-500">Monthly Youth Dues</dt>
                    <dd class="font-semibold text-slate-800">{{ $monthlyDues->format() }} ({{ $monthlyDues->pesewas }}p)</dd>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <dt class="text-slate-500">Cash Approval Required</dt>
                    <dd class="font-semibold text-emerald-700">{{ config('pyc.finance.cash_payment_requires_approval') ? 'Mandatory' : 'No' }}</dd>
                </div>
                <div class="flex justify-between py-1.5">
                    <dt class="text-slate-500">Financial Deletion</dt>
                    <dd class="font-semibold text-rose-700">Prohibited (Reversals Only)</dd>
                </div>
            </dl>
        </div>
    </div>
@endcomponent
