<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Services;

use App\Models\User;
use App\Modules\Notifications\DTOs\NotificationRecipient;
use App\Modules\Notifications\Models\NotificationConsent;
use App\Modules\Notifications\Models\NotificationPreference;
use App\Modules\Notifications\Models\NotificationSuppression;
use App\Support\Audit\AuditLogger;

/**
 * Enforces member preferences, communication consent, and hard suppressions (Spec Section 10.15).
 */
class NotificationConsentService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Determines whether a notification can be sent to $recipient on $channel.
     * Essential transactional/security notices are NEVER suppressed by optional opt-outs,
     * unless the recipient address is on the active invalid/bounce suppression list.
     */
    public function canSend(
        NotificationRecipient $recipient,
        string $channel,
        string $category,
        bool $isTransactional,
    ): bool {
        $address = match ($channel) {
            'sms' => $recipient->phone,
            'email' => $recipient->email,
            'in_app' => $recipient->userId !== null ? 'user:'.$recipient->userId : null,
            default => null,
        };

        if ($address === null || $address === '') {
            return false;
        }

        if ($channel !== 'in_app' && $this->isSuppressed($channel, $address)) {
            return false;
        }

        // Essential transactional/security/receipt/invitation notices bypass optional opt-outs (Spec 10.15)
        if ($isTransactional) {
            return true;
        }

        // Check user preference toggles if associated with a portal User
        if ($recipient->userId !== null) {
            $pref = NotificationPreference::query()->where('user_id', $recipient->userId)->first();
            if ($pref !== null && ! $pref->allowsOptionalChannel($channel, $category)) {
                return false;
            }
        }

        // Check explicit consent withdrawal history
        if ($channel !== 'in_app') {
            $latestConsent = NotificationConsent::query()
                ->where('channel', $channel)
                ->where(static function ($query) use ($recipient, $address): void {
                    $query->where('recipient_identifier', $address);
                    if ($recipient->userId !== null) {
                        $query->orWhere('user_id', $recipient->userId);
                    }
                })
                ->whereIn('purpose', [$category, 'announcements', 'optional'])
                ->latest('id')
                ->first();

            if ($latestConsent !== null && $latestConsent->status === 'withdrawn') {
                return false;
            }
        }

        return true;
    }

    public function isSuppressed(string $channel, string $recipientAddress): bool
    {
        return NotificationSuppression::query()
            ->where('channel', $channel)
            ->where('recipient_address', $recipientAddress)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * @param  array<string, bool>  $categoryPreferences
     */
    public function updatePreferences(
        User $user,
        bool $smsEnabled,
        bool $emailEnabled,
        bool $inAppEnabled,
        array $categoryPreferences = [],
        ?User $actor = null,
    ): NotificationPreference {
        $resolvedActor = $actor ?? $user;

        $preference = NotificationPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'sms_enabled' => $smsEnabled,
                'email_enabled' => $emailEnabled,
                'in_app_enabled' => $inAppEnabled,
                'category_preferences' => $categoryPreferences,
            ]
        );

        foreach (['sms' => [$user->phone, $smsEnabled], 'email' => [$user->email, $emailEnabled]] as $channel => [$identifier, $granted]) {
            if (! empty($identifier)) {
                $this->recordConsentState(
                    user: $user,
                    recipientIdentifier: (string) $identifier,
                    channel: $channel,
                    purpose: 'announcements',
                    granted: (bool) $granted,
                    source: 'portal_preferences',
                    actor: $resolvedActor,
                );
            }
        }

        $this->auditLogger->log(
            event: 'notifications.preferences_updated',
            category: 'notifications',
            description: "Updated communication preferences for [{$user->email}].",
            auditable: $preference,
            newValues: [
                'sms_enabled' => $smsEnabled,
                'email_enabled' => $emailEnabled,
                'in_app_enabled' => $inAppEnabled,
                'category_preferences' => $categoryPreferences,
            ],
            actor: $resolvedActor,
        );

        return $preference;
    }

    public function recordConsentState(
        ?User $user,
        string $recipientIdentifier,
        string $channel,
        string $purpose,
        bool $granted,
        string $source = 'portal_preferences',
        string $lawfulBasis = 'consent',
        ?User $actor = null,
    ): NotificationConsent {
        return NotificationConsent::query()->create([
            'user_id' => $user?->id,
            'recipient_identifier' => $recipientIdentifier,
            'channel' => $channel,
            'purpose' => $purpose,
            'status' => $granted ? 'granted' : 'withdrawn',
            'lawful_basis' => $lawfulBasis,
            'source' => $source,
            'consented_at' => $granted ? now() : null,
            'withdrawn_at' => $granted ? null : now(),
            'recorded_by' => $actor?->id,
        ]);
    }
}
