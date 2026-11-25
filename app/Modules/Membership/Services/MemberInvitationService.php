<?php

declare(strict_types=1);

namespace App\Modules\Membership\Services;

use App\Models\User;
use App\Modules\Membership\Enums\MemberStatus;
use App\Modules\Membership\Models\Member;
use App\Modules\Membership\Models\MemberAccount;
use App\Modules\Membership\Models\MemberInvitation;
use App\Modules\Notifications\DTOs\DispatchNotificationData;
use App\Modules\Notifications\DTOs\NotificationRecipient;
use App\Modules\Notifications\Services\NotificationConsentService;
use App\Modules\Notifications\Services\NotificationOrchestrator;
use App\Support\Audit\AuditLogger;
use App\Support\Security\PycRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Issues single-use, time-limited, SHA-256 hashed portal invitations only after approval (Spec Section 5.2 & 5.4).
 */
class MemberInvitationService
{
    public function __construct(
        private readonly NotificationOrchestrator $notificationOrchestrator,
        private readonly NotificationConsentService $consentService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return array{invitation: MemberInvitation, plain_token: string, url: string}
     */
    public function invite(Member $member, ?User $actor = null): array
    {
        if ($member->status !== MemberStatus::Active->value || empty($member->member_number)) {
            throw new InvalidArgumentException('Portal invitations can only be issued to approved active members.');
        }

        if ($member->account()->exists()) {
            throw new InvalidArgumentException('This member already has an activated portal account.');
        }

        if (empty($member->email) && empty($member->phone)) {
            throw new InvalidArgumentException('Cannot send portal invitation without an email address or phone number.');
        }

        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);
        $ttlHours = max(1, (int) config('pyc.membership.invitation_ttl_hours', 72));

        $invitation = DB::transaction(function () use ($member, $tokenHash, $ttlHours, $actor): MemberInvitation {
            // Revoke any prior unused invitations for this member
            MemberInvitation::query()
                ->where('member_id', $member->id)
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $created = MemberInvitation::query()->create([
                'member_id' => $member->id,
                'email' => $member->email,
                'phone' => $member->phone,
                'token_hash' => $tokenHash,
                'expires_at' => now()->addHours($ttlHours),
                'invited_by' => $actor?->id,
            ]);

            $this->auditLogger->log(
                event: 'members.invitation_issued',
                category: 'membership',
                description: "Issued secure portal invitation for member [{$member->member_number}] ({$member->fullName()}).",
                auditable: $member,
                metadata: [
                    'invitation_id' => $created->id,
                    'expires_at' => $created->expires_at->toIso8601String(),
                ],
                actor: $actor,
            );

            return $created;
        });

        $invitationUrl = route('invitations.show', ['token' => $plainToken]);

        $this->notificationOrchestrator->dispatch(new DispatchNotificationData(
            idempotencyKey: "account_invitation:{$invitation->id}",
            templateCode: 'account_invitation',
            recipient: new NotificationRecipient(
                memberId: (int) $member->id,
                name: $member->fullName(),
                email: $member->email,
                phone: $member->phone,
            ),
            variables: [
                'member_name' => $member->first_name,
                'invitation_url' => $invitationUrl,
                'expires_in' => "{$ttlHours} hours",
            ],
            channels: ['sms', 'email'],
            eventName: 'membership.invitation_sent',
            relatedType: $member->getMorphClass(),
            relatedId: (int) $member->id,
            initiatedByUserId: $actor?->id,
            failSafely: true,
        ));

        return [
            'invitation' => $invitation,
            'plain_token' => $plainToken,
            'url' => $invitationUrl,
        ];
    }

    public function findUsableByToken(string $plainToken): ?MemberInvitation
    {
        if (trim($plainToken) === '') {
            return null;
        }

        $tokenHash = hash('sha256', $plainToken);

        $invitation = MemberInvitation::query()
            ->with('member')
            ->where('token_hash', $tokenHash)
            ->first();

        if ($invitation === null || ! $invitation->isUsable()) {
            return null;
        }

        if ($invitation->member === null || $invitation->member->status !== MemberStatus::Active->value) {
            return null;
        }

        return $invitation;
    }

    public function acceptInvitation(string $plainToken, string $email, string $password, bool $optionalConsent = true): User
    {
        $tokenHash = hash('sha256', $plainToken);
        $normalizedEmail = strtolower(trim($email));

        [$user, $member] = DB::transaction(function () use ($tokenHash, $normalizedEmail, $password): array {
            $invitation = MemberInvitation::query()
                ->with('member')
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if ($invitation === null || ! $invitation->isUsable()) {
                throw new InvalidArgumentException('This invitation link is invalid, expired, or has already been used.');
            }

            $member = Member::query()->lockForUpdate()->findOrFail($invitation->member_id);

            if ($member->status !== MemberStatus::Active->value) {
                throw new InvalidArgumentException('Only approved active members can activate a portal account.');
            }

            if ($member->account()->exists()) {
                throw new InvalidArgumentException('A portal account is already active for this member.');
            }

            if (User::query()->where('email', $normalizedEmail)->exists()) {
                throw new InvalidArgumentException('This email address is already registered to an existing portal account.');
            }

            if (empty($member->email)) {
                $member->update(['email' => $normalizedEmail]);
            }

            $user = new User([
                'name' => $member->fullName(),
                'email' => $normalizedEmail,
                'phone' => $member->phone,
                'password' => $password,
                'status' => 'active',
                'must_change_password' => false,
            ]);
            $user->email_verified_at = now();
            $user->save();

            $user->assignRole(PycRole::Member->value);

            MemberAccount::query()->create([
                'member_id' => $member->id,
                'user_id' => $user->id,
                'status' => 'active',
                'activated_at' => now(),
            ]);

            $invitation->update(['used_at' => now()]);

            $this->auditLogger->log(
                event: 'members.portal_account_activated',
                category: 'membership',
                description: "Activated portal account for member [{$member->member_number}] ({$user->email}).",
                auditable: $member,
                actor: $user,
            );

            return [$user, $member];
        });

        $this->consentService->updatePreferences(
            user: $user,
            smsEnabled: $optionalConsent,
            emailEnabled: $optionalConsent,
            inAppEnabled: true,
            categoryPreferences: ['events' => $optionalConsent, 'announcements' => $optionalConsent],
            actor: $user,
        );

        $this->notificationOrchestrator->dispatch(new DispatchNotificationData(
            idempotencyKey: "member_welcome:{$member->id}",
            templateCode: 'member_welcome',
            recipient: new NotificationRecipient(
                userId: (int) $user->id,
                memberId: (int) $member->id,
                name: $member->fullName(),
                email: $user->email,
                phone: $member->phone,
            ),
            variables: [
                'member_name' => $member->first_name,
                'member_number' => (string) $member->member_number,
            ],
            channels: ['sms', 'email', 'in_app'],
            eventName: 'membership.portal_activated',
            relatedType: $member->getMorphClass(),
            relatedId: (int) $member->id,
            initiatedByUserId: (int) $user->id,
            failSafely: true,
        ));

        return $user;
    }
}
