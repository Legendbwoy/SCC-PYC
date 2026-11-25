@component('layouts.app', [
    'title' => 'Notification Preferences',
    'heading' => 'Notification Preferences',
    'subheading' => 'Choose how you want to be notified. Essential security, invitation, and payment receipt notices always remain active.',
])
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Main Toggle Preferences Card (Matching Bottom-Right Reference Mockup) -->
        <div class="lg:col-span-7 pyc-card p-6 sm:p-7 space-y-5">
            <form method="POST" action="{{ route('profile.notifications.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <!-- Email Notifications -->
                <label class="flex items-center justify-between gap-4 p-4 rounded-2xl bg-slate-50/80 border border-slate-100 hover:border-sky-200 transition cursor-pointer">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="h-11 w-11 rounded-xl bg-sky-100 text-[#1E549F] flex items-center justify-center shrink-0">
                            <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-900">Email Notifications</p>
                            <p class="text-xs text-slate-500 truncate">Event updates, statements &amp; news ({{ $user->email }})</p>
                        </div>
                    </div>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="email_enabled" value="1" @checked($preference->email_enabled) class="sr-only peer">
                        <span class="w-11 h-6 bg-slate-300 rounded-full peer peer-checked:bg-[#1D56A6] after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></span>
                    </span>
                </label>

                <!-- SMS Notifications -->
                <label class="flex items-center justify-between gap-4 p-4 rounded-2xl bg-slate-50/80 border border-slate-100 hover:border-sky-200 transition cursor-pointer">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="h-11 w-11 rounded-xl bg-sky-100 text-[#1E549F] flex items-center justify-center shrink-0">
                            <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-900">SMS Notifications</p>
                            <p class="text-xs text-slate-500 truncate">Important alerts and monthly reminders ({{ $user->phone ?: 'No phone on file' }})</p>
                        </div>
                    </div>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="sms_enabled" value="1" @checked($preference->sms_enabled) class="sr-only peer">
                        <span class="w-11 h-6 bg-slate-300 rounded-full peer peer-checked:bg-[#1D56A6] after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></span>
                    </span>
                </label>

                <!-- Event Announcements -->
                <label class="flex items-center justify-between gap-4 p-4 rounded-2xl bg-slate-50/80 border border-slate-100 hover:border-sky-200 transition cursor-pointer">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="h-11 w-11 rounded-xl bg-sky-100 text-[#1E549F] flex items-center justify-center shrink-0">
                            <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-900">Event Announcements</p>
                            <p class="text-xs text-slate-500 truncate">Parish youth retreats, fellowships &amp; activities</p>
                        </div>
                    </div>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="category_events" value="1" @checked(($preference->category_preferences['events'] ?? true)) class="sr-only peer">
                        <span class="w-11 h-6 bg-slate-300 rounded-full peer peer-checked:bg-[#1D56A6] after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></span>
                    </span>
                </label>

                <!-- Council Announcements & In-App Alerts -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <label class="flex items-center justify-between gap-3 p-3.5 rounded-2xl bg-slate-50/80 border border-slate-100 cursor-pointer">
                        <div>
                            <p class="text-xs font-bold text-slate-900">Council Broadcasts</p>
                            <p class="text-[11px] text-slate-500">General PYC news</p>
                        </div>
                        <span class="relative inline-flex items-center shrink-0">
                            <input type="checkbox" name="category_announcements" value="1" @checked(($preference->category_preferences['announcements'] ?? true)) class="sr-only peer">
                            <span class="w-10 h-5.5 bg-slate-300 rounded-full peer peer-checked:bg-[#1D56A6] after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4.5 after:w-4.5 after:transition-all peer-checked:after:translate-x-4.5"></span>
                        </span>
                    </label>

                    <label class="flex items-center justify-between gap-3 p-3.5 rounded-2xl bg-slate-50/80 border border-slate-100 cursor-pointer">
                        <div>
                            <p class="text-xs font-bold text-slate-900">In-App Portal Inbox</p>
                            <p class="text-[11px] text-slate-500">Dashboard bell alerts</p>
                        </div>
                        <span class="relative inline-flex items-center shrink-0">
                            <input type="checkbox" name="in_app_enabled" value="1" @checked($preference->in_app_enabled) class="sr-only peer">
                            <span class="w-10 h-5.5 bg-slate-300 rounded-full peer peer-checked:bg-[#1D56A6] after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4.5 after:w-4.5 after:transition-all peer-checked:after:translate-x-4.5"></span>
                        </span>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn-pyc-primary w-full py-3">
                        Save Preferences
                    </button>
                </div>
            </form>
        </div>

        <!-- In-App Notifications Inbox -->
        <div class="lg:col-span-5 pyc-card p-6 space-y-4">
            <h2 class="text-lg font-extrabold text-slate-900">Recent Portal Alerts</h2>
            <div class="space-y-3">
                @forelse($inAppNotifications as $alert)
                    <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-100 text-xs">
                        <div class="flex justify-between gap-2">
                            <p class="font-bold text-slate-900">{{ $alert->data['title'] ?? 'PYC Notice' }}</p>
                            <span class="text-slate-400 shrink-0">{{ $alert->created_at?->diffForHumans() }}</span>
                        </div>
                        <p class="mt-1 text-slate-600 leading-relaxed">{{ strip_tags((string) ($alert->data['message'] ?? '')) }}</p>
                    </div>
                @empty
                    <div class="py-10 text-center text-xs text-slate-400">
                        No recent in-app portal alerts.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Consent History -->
    <div class="pyc-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-extrabold text-slate-900">My Consent &amp; Opt-Out History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200/80 text-xs">
                <thead class="bg-slate-50/80 uppercase text-slate-500">
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
                            <td class="px-5 py-3 uppercase font-bold text-[#0F2D59]">{{ $item->channel }}</td>
                            <td class="px-5 py-3 capitalize text-slate-700">{{ $item->purpose }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2.5 py-0.5 rounded-full font-bold {{ $item->status === 'granted' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $item->source }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-400">No consent changes recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endcomponent
