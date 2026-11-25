@component('layouts.app', [
    'title' => 'Bulk Campaigns',
    'heading' => 'Bulk Announcements & Scheduled Campaigns',
    'subheading' => 'Permission-controlled, rate-limited, and audited bulk communications respecting member consent.',
])
    @include('portal.communications.partials.nav')

    @can('notifications.send_bulk')
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <h2 class="text-base font-bold text-slate-900 mb-1">Compose Authorized Bulk Announcement</h2>
            <p class="text-xs text-slate-500 mb-4">Dispatched onto the dedicated <code class="text-teal-800">bulk_notifications</code> queue so payment receipts are never delayed.</p>

            <form method="POST" action="{{ route('communications.campaigns.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700">Campaign Title</label>
                        <input type="text" name="title" required placeholder="Youth General Meeting Reminder"
                               class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Purpose Category</label>
                        <select name="category" class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                            <option value="announcements">General Announcements</option>
                            <option value="events">Parish Youth Events</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-5 text-xs">
                    <span class="font-semibold text-slate-700">Channels:</span>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="channels[]" value="sms" checked class="rounded border-slate-300 text-teal-700">
                        SMS
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="channels[]" value="email" checked class="rounded border-slate-300 text-teal-700">
                        Email
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">SMS Body (concise plain text)</label>
                        <textarea name="sms_body" rows="3" placeholder="SCC-PYC Notice: General meeting this Sunday after 9am Mass..."
                                  class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Email Body</label>
                        <textarea name="email_body" rows="3" placeholder="Dear PYC Member, ..."
                                  class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm"></textarea>
                    </div>
                </div>

                <div class="flex flex-wrap items-end justify-between gap-4">
                    @can('notifications.schedule')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Optional Schedule Time (leave blank to send immediately)</label>
                            <input type="datetime-local" name="scheduled_at"
                                   class="mt-1 block rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                        </div>
                    @endcan

                    <button type="submit"
                            class="rounded-xl px-5 py-2.5 text-xs font-semibold text-white cursor-pointer"
                            style="background-color: var(--pyc-primary);">
                        Authorize &amp; Dispatch Campaign
                    </button>
                </div>
            </form>
        </div>
    @endcan

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-base font-bold text-slate-900">Campaign Audit History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Reference</th>
                        <th class="px-5 py-3 text-left font-semibold">Title &amp; Channels</th>
                        <th class="px-5 py-3 text-left font-semibold">Status</th>
                        <th class="px-5 py-3 text-left font-semibold">Recipients</th>
                        <th class="px-5 py-3 text-left font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($campaigns as $camp)
                        <tr>
                            <td class="px-5 py-3.5 font-mono text-xs text-teal-800">{{ $camp->reference }}</td>
                            <td class="px-5 py-3.5 text-xs">
                                <p class="font-semibold text-slate-900">{{ $camp->title }}</p>
                                <p class="text-slate-500 uppercase text-[10px]">{{ implode(', ', (array) $camp->channels) }} &bull; by {{ $camp->creator?->name }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-xs capitalize font-medium">{{ $camp->status }}</td>
                            <td class="px-5 py-3.5 text-xs">{{ number_format($camp->total_recipients) }}</td>
                            <td class="px-5 py-3.5 text-xs">
                                @if(in_array($camp->status, ['draft', 'scheduled']) && auth()->user()->can('notifications.cancel'))
                                    <form method="POST" action="{{ route('communications.campaigns.cancel', $camp) }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-rose-700 hover:text-rose-800 cursor-pointer">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-400">No bulk campaigns created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endcomponent
