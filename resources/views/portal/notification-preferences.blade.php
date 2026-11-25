@component('layouts.app', [
    'title' => 'Notifications & Preferences',
    'heading' => 'Notifications, Preferences & Consent History',
    'subheading' => 'Manage optional SMS/email communication channels. Essential account, security, and payment receipt notices always remain active.',
])
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <h2 class="text-base font-bold text-slate-900 mb-1">Optional Communication Preferences</h2>
            <p class="text-xs text-slate-500 mb-4">
                Essential transactional notices (account invitations, security alerts, and official payment receipts) are never suppressed when optional announcements are turned off.
            </p>

            <form method="POST" action="{{ route('profile.notifications.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div class="space-y-2.5 text-sm">
                    <label class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span>Receive optional SMS alerts ({{ $user->phone ?: 'No phone on file' }})</span>
                        <input type="checkbox" name="sms_enabled" value="1" @checked($preference->sms_enabled) class="h-4 w-4 rounded border-slate-300 text-teal-700">
                    </label>

                    <label class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span>Receive optional Email notices ({{ $user->email }})</span>
                        <input type="checkbox" name="email_enabled" value="1" @checked($preference->email_enabled) class="h-4 w-4 rounded border-slate-300 text-teal-700">
                    </label>

                    <label class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span>Receive In-App portal alerts</span>
                        <input type="checkbox" name="in_app_enabled" value="1" @checked($preference->in_app_enabled) class="h-4 w-4 rounded border-slate-300 text-teal-700">
                    </label>
                </div>

                <div class="pt-2 border-t border-slate-100 space-y-2 text-xs text-slate-700">
                    <p class="font-semibold text-slate-800">Optional Topics:</p>
                    <label class="inline-flex items-center gap-2 mr-4">
                        <input type="checkbox" name="category_events" value="1" @checked(($preference->category_preferences['events'] ?? true)) class="rounded border-slate-300 text-teal-700">
                        Parish Youth Events &amp; Reminders
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="category_announcements" value="1" @checked(($preference->category_preferences['announcements'] ?? true)) class="rounded border-slate-300 text-teal-700">
                        General Council Announcements
                    </label>
                </div>

                <button type="submit"
                        class="rounded-xl px-4 py-2 text-xs font-semibold text-white cursor-pointer"
                        style="background-color: var(--pyc-primary);">
                    Save Communication Preferences
                </button>
            </form>
        </div>

        <!-- In-App Notifications Inbox -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4">
            <h2 class="text-base font-bold text-slate-900">Recent In-App Portal Alerts</h2>
            <div class="space-y-2.5">
                @forelse($inAppNotifications as $alert)
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                        <div class="flex justify-between gap-2">
                            <p class="font-semibold text-slate-900">{{ $alert->data['title'] ?? 'PYC Notice' }}</p>
                            <span class="text-slate-400">{{ $alert->created_at?->diffForHumans() }}</span>
                        </div>
                        <p class="mt-1 text-slate-600">{{ strip_tags((string) ($alert->data['message'] ?? '')) }}</p>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 py-6 text-center">No in-app alerts yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Consent History -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-base font-bold text-slate-900">My Consent &amp; Opt-Out History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Recorded At</th>
                        <th class="px-5 py-3 text-left font-semibold">Channel</th>
                        <th class="px-5 py-3 text-left font-semibold">Purpose</th>
                        <th class="px-5 py-3 text-left font-semibold">Status</th>
                        <th class="px-5 py-3 text-left font-semibold">Source</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($consentHistory as $item)
                        <tr>
                            <td class="px-5 py-3 text-slate-500">{{ $item->created_at?->timezone('Africa/Accra')->format('d M Y H:i') }}</td>
                            <td class="px-5 py-3 uppercase font-semibold text-slate-700">{{ $item->channel }}</td>
                            <td class="px-5 py-3 capitalize text-slate-700">{{ $item->purpose }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full font-semibold {{ $item->status === 'granted' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $item->source }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-6 text-center text-slate-400">No consent changes recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endcomponent
