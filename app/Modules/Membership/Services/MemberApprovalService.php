<?php

declare(strict_types=1);

namespace App\Modules\Membership\Services;

use App\Models\User;
use App\Modules\Membership\Enums\MemberStatus;
use App\Modules\Membership\Models\Member;
use App\Modules\Membership\Models\MemberApproval;
use App\Modules\Membership\Models\MembershipStatusHistory;
use App\Modules\Notifications\DTOs\DispatchNotificationData;
use App\Modules\Notifications\DTOs\NotificationRecipient;
use App\Modules\Notifications\Services\NotificationOrchestrator;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Coordinates Secretary approval, rejection, and archival of PYC members (Spec Section 5.1, 5.2 & 5.4).
 */
class MemberApprovalService
{
    public function __construct(
        private readonly MemberNumberService $memberNumberService,
        private readonly MemberInvitationService $invitationService,
        private readonly NotificationOrchestrator $notificationOrchestrator,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function approve(
        Member $member,
        User $reviewer,
        ?string $reason = null,
        bool $sendInvitation = true,
    ): Member {
        $approvedMember = DB::transaction(function () use ($member, $reviewer, $reason): Member {
            $locked = Member::query()->lockForUpdate()->findOrFail($member->id);

            if ($locked->status === MemberStatus::Active->value) {
                throw new InvalidArgumentException('Member application is already approved and active.');
            }

            $previousStatus = $locked->status;
            $memberNumber = $locked->member_number ?: $this->memberNumberService->generate();

            $locked->update([
                'member_number' => $memberNumber,
                'status' => MemberStatus::Active->value,
                'approved_at' => now(),
                'joined_at' => $locked->joined_at ?? now()->toDateString(),
                'is_duplicate_flagged' => false,
            ]);

            MemberApproval::query()->create([
                'member_id' => $locked->id,
                'reviewer_id' => $reviewer->id,
                'decision' => 'approved',
                'reason' => $reason ?: 'Approved by Secretary review',
            ]);

            MembershipStatusHistory::query()->create([
                'member_id' => $locked->id,
                'from_status' => $previousStatus,
                'to_status' => MemberStatus::Active->value,
                'reason' => $reason ?: 'Membership approved',
                'changed_by' => $reviewer->id,
            ]);

            $this->auditLogger->log(
                event: 'members.approved',
                category: 'membership',
                description: "Approved member [{$locked->fullName()}] with number [{$memberNumber}].",
                auditable: $locked,
                oldValues: ['status' => $previousStatus],
                newValues: ['status' => MemberStatus::Active->value, 'member_number' => $memberNumber],
                actor: $reviewer,
            );

            return $locked;
        });

        $this->notificationOrchestrator->dispatch(new DispatchNotificationData(
            idempotencyKey: "membership_approved:member:{$approvedMember->id}",
            templateCode: 'membership_approved',
            recipient: new NotificationRecipient(
                memberId: (int) $approvedMember->id,
                name: $approvedMember->fullName(),
                email: $approvedMember->email,
                phone: $approvedMember->phone,
            ),
            variables: [
                'member_name' => $approvedMember->first_name,
                'member_number' => (string) $approvedMember->member_number,
            ],
            channels: ['sms', 'email'],
            eventName: 'membership.approved',
            relatedType: $approvedMember->getMorphClass(),
            relatedId: (int) $approvedMember->id,
            initiatedByUserId: (int) $reviewer->id,
            failSafely: true,
        ));

        if ($sendInvitation && (! empty($approvedMember->email) || ! empty($approvedMember->phone))) {
            $this->invitationService->invite($approvedMember, $reviewer);
        }

        return $approvedMember->fresh(['approvals', 'invitations']) ?? $approvedMember;
    }

    public function reject(Member $member, User $reviewer, string $reason): Member
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A rejection reason is required for auditability.');
        }

        $rejectedMember = DB::transaction(function () use ($member, $reviewer, $reason): Member {
            $locked = Member::query()->lockForUpdate()->findOrFail($member->id);

            if ($locked->status === MemberStatus::Active->value) {
                throw new InvalidArgumentException('Approved active members cannot be rejected; use archive instead.');
            }

            $previousStatus = $locked->status;

            $locked->update([
                'status' => MemberStatus::Rejected->value,
            ]);

            MemberApproval::query()->create([
                'member_id' => $locked->id,
                'reviewer_id' => $reviewer->id,
                'decision' => 'rejected',
                'reason' => $reason,
            ]);

            MembershipStatusHistory::query()->create([
                'member_id' => $locked->id,
                'from_status' => $previousStatus,
                'to_status' => MemberStatus::Rejected->value,
                'reason' => $reason,
                'changed_by' => $reviewer->id,
            ]);

            $this->auditLogger->log(
                event: 'members.rejected',
                category: 'membership',
                description: "Rejected membership application for [{$locked->fullName()}].",
                auditable: $locked,
                oldValues: ['status' => $previousStatus],
                newValues: ['status' => MemberStatus::Rejected->value, 'reason' => $reason],
                actor: $reviewer,
            );

            return $locked;
        });

        $this->notificationOrchestrator->dispatch(new DispatchNotificationData(
            idempotencyKey: "membership_rejected:member:{$rejectedMember->id}:".md5($reason),
            templateCode: 'membership_rejected',
            recipient: new NotificationRecipient(
                memberId: (int) $rejectedMember->id,
                name: $rejectedMember->fullName(),
                email: $rejectedMember->email,
                phone: $rejectedMember->phone,
            ),
            variables: [
                'applicant_name' => $rejectedMember->first_name,
                'reason' => $reason,
            ],
            channels: ['sms', 'email'],
            eventName: 'membership.rejected',
            relatedType: $rejectedMember->getMorphClass(),
            relatedId: (int) $rejectedMember->id,
            initiatedByUserId: (int) $reviewer->id,
            failSafely: true,
        ));

        return $rejectedMember->fresh(['approvals']) ?? $rejectedMember;
    }

    public function archive(Member $member, User $actor, string $reason): Member
    {
        return DB::transaction(function () use ($member, $actor, $reason): Member {
            $locked = Member::query()->with('account.user')->lockForUpdate()->findOrFail($member->id);
            $previousStatus = $locked->status;

            $locked->update(['status' => MemberStatus::Archived->value]);

            if ($locked->account !== null) {
                $locked->account->update(['status' => 'suspended']);
                $locked->account->user?->update(['status' => 'suspended']);
            }

            MembershipStatusHistory::query()->create([
                'member_id' => $locked->id,
                'from_status' => $previousStatus,
                'to_status' => MemberStatus::Archived->value,
                'reason' => $reason,
                'changed_by' => $actor->id,
            ]);

            $this->auditLogger->log(
                event: 'members.archived',
                category: 'membership',
                description: "Archived member [{$locked->member_number}] ({$locked->fullName()}).",
                auditable: $locked,
                oldValues: ['status' => $previousStatus],
                newValues: ['status' => MemberStatus::Archived->value, 'reason' => $reason],
                actor: $actor,
            );

            return $locked;
        });
    }
}
