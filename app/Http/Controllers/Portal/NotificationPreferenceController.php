<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Notifications\Models\NotificationConsent;
use App\Modules\Notifications\Models\NotificationPreference;
use App\Modules\Notifications\Services\NotificationConsentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function __construct(
        private readonly NotificationConsentService $consentService,
    ) {}

    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $preference = NotificationPreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'sms_enabled' => true,
                'email_enabled' => true,
                'in_app_enabled' => true,
                'category_preferences' => [
                    'events' => true,
                    'announcements' => true,
                ],
            ]
        );

        $consentHistory = NotificationConsent::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(15)
            ->get();

        return view('portal.notification-preferences', [
            'user' => $user,
            'preference' => $preference,
            'consentHistory' => $consentHistory,
            'inAppNotifications' => $user->notifications()->latest()->limit(15)->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->consentService->updatePreferences(
            user: $user,
            smsEnabled: $request->boolean('sms_enabled'),
            emailEnabled: $request->boolean('email_enabled'),
            inAppEnabled: $request->boolean('in_app_enabled'),
            categoryPreferences: [
                'events' => $request->boolean('category_events'),
                'announcements' => $request->boolean('category_announcements'),
            ],
            actor: $user,
        );

        return redirect()
            ->route('profile.notifications')
            ->with('status', 'Your communication preferences and consent record have been updated.');
    }
}
