@component('layouts.app', [
    'title' => 'Notification Templates',
    'heading' => 'Notification Templates & Immutable Version History',
    'subheading' => 'Separate channel templates with strict placeholder validation and GSM-7 / UCS-2 segment estimation.',
])
    @include('portal.communications.partials.nav')

    @can('notifications.manage_templates')
        <details class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <summary class="cursor-pointer text-sm font-bold text-slate-900">
                + Create Template or Publish New Immutable Version
            </summary>
            <form method="POST" action="{{ route('communications.templates.store') }}" class="mt-4 space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Template Code</label>
                        <input type="text" name="code" required placeholder="monthly_dues_reminder"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Display Name</label>
                        <input type="text" name="name" required placeholder="Monthly Dues Reminder"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Category</label>
                        <select name="category" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white">
                            <option value="membership">membership</option>
                            <option value="finance">finance</option>
                            <option value="events">events</option>
                            <option value="security">security</option>
                            <option value="general">general</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Channel</label>
                        <select name="channel" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white">
                            <option value="sms">sms</option>
                            <option value="email">email</option>
                            <option value="in_app">in_app</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Subject (Email / In-App)</label>
                        <input type="text" name="subject" placeholder="Official PYC Notice"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Required Variables (comma-separated)</label>
                        <input type="text" name="required_variables_csv" placeholder="member_name, amount, period"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700">Template Body (use &#123;&#123;variable&#125;&#125; placeholders)</label>
                    <textarea name="body" rows="3" required
                              class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-mono"></textarea>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                        <input type="checkbox" name="is_transactional" value="1" checked class="rounded border-slate-300 text-teal-700">
                        Essential transactional/security notice (not suppressed by optional marketing opt-out)
                    </label>
                    <button type="submit"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-white cursor-pointer"
                            style="background-color: var(--pyc-primary);">
                        Save New Template Version
                    </button>
                </div>
            </form>
        </details>
    @endcan

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Code / Name</th>
                        <th class="px-5 py-3 text-left font-semibold">Channel</th>
                        <th class="px-5 py-3 text-left font-semibold">Version</th>
                        <th class="px-5 py-3 text-left font-semibold">Variables &amp; Preview</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($templates as $tpl)
                        @php
                            $ver = $tpl->currentVersion;
                            $gsm = ($tpl->channel === 'sms' && $ver) ? $gsmEstimator->estimate($ver->body) : null;
                        @endphp
                        <tr class="align-top">
                            <td class="px-5 py-3.5">
                                <p class="font-mono text-xs font-semibold text-teal-800">{{ $tpl->code }}</p>
                                <p class="text-xs text-slate-700 font-medium">{{ $tpl->name }}</p>
                                <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-medium {{ $tpl->is_transactional ? 'bg-slate-100 text-slate-700' : 'bg-amber-50 text-amber-800' }}">
                                    {{ $tpl->is_transactional ? 'Transactional' : 'Optional / Consent' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase bg-slate-100 text-slate-700">
                                    {{ $tpl->channel }}
                                </span>
                                @if($gsm)
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $gsm['encoding'] }} &bull; ~{{ $gsm['segments'] }} seg</p>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs">
                                <span class="font-mono font-semibold text-slate-900">v{{ $ver?->version_number ?? 1 }}</span>
                                <p class="text-[11px] text-slate-400">{{ $tpl->versions->count() }} total ver.</p>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-700 space-y-1">
                                @if($ver?->subject)
                                    <p class="font-semibold text-slate-900">Subject: {{ $ver->subject }}</p>
                                @endif
                                <p class="text-slate-600 font-mono text-[11px] leading-relaxed">{{ $ver?->body }}</p>
                                @if(!empty($ver?->required_variables))
                                    <div class="flex flex-wrap gap-1 pt-1">
                                        @foreach($ver->required_variables as $varName)
                                            <span class="px-1.5 py-0.5 rounded bg-teal-50 text-teal-800 font-mono text-[10px]">&#123;&#123;{{ $varName }}&#125;&#125;</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endcomponent
