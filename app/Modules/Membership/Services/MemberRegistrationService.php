<?php

declare(strict_types=1);

namespace App\Modules\Membership\Services;

use App\Models\User;
use App\Modules\Membership\Enums\MemberStatus;
use App\Modules\Membership\Models\Member;
use App\Modules\Membership\Models\MemberGroup;
use App\Modules\Membership\Models\MemberGroupAssignment;
use App\Modules\Membership\Models\MembershipStatusHistory;
use App\Modules\Membership\Models\MemberSubmission;
use App\Modules\Notifications\DTOs\DispatchNotificationData;
use App\Modules\Notifications\DTOs\NotificationRecipient;
use App\Modules\Notifications\Services\NotificationOrchestrator;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates pending membership applications without creating active portal accounts (Spec Section 5.2 & 5.4).
 */
class MemberRegistrationService
{
    public function __construct(
        private readonly MemberDuplicateService $duplicateService,
        private readonly NotificationOrchestrator $notificationOrchestrator,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $validatedData
     */
    public function createPending(
        array $validatedData,
        ?MemberSubmission $submission = null,
        ?User $actor = null,
    ): Member {
        $duplicates = $this->duplicateService->findMatches($validatedData);
        $duplicateIds = $duplicates->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
        $isDuplicateFlagged = $duplicateIds !== [];

        $member = DB::transaction(function () use ($validatedData, $submission, $actor, $isDuplicateFlagged, $duplicateIds): Member {
            $member = Member::query()->create([
                'first_name' => $validatedData['first_name'],
                'middle_name' => $validatedData['middle_name'] ?? null,
                'last_name' => $validatedData['last_name'],
                'phone' => $validatedData['phone'] ?? null,
                'email' => $validatedData['email'] ?? null,
                'date_of_birth' => $validatedData['date_of_birth'] ?? null,
                'gender' => $validatedData['gender'] ?? null,
                'address' => $validatedData['address'] ?? null,
                'occupation' => $validatedData['occupation'] ?? null,
                'emergency_contact_name' => $validatedData['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validatedData['emergency_contact_phone'] ?? null,
                'status' => MemberStatus::Pending->value,
                'is_duplicate_flagged' => $isDuplicateFlagged,
                'duplicate_candidate_ids' => $isDuplicateFlagged ? $duplicateIds : null,
            ]);

            MembershipStatusHistory::query()->create([
                'member_id' => $member->id,
                'from_status' => null,
                'to_status' => MemberStatus::Pending->value,
                'reason' => $submission !== null
                    ? "Imported from {$submission->source} ({$submission->external_response_id})"
                    : 'Manual pending application created',
                'changed_by' => $actor?->id,
            ]);

            if (! empty($validatedData['group_name'])) {
                $groupName = trim((string) $validatedData['group_name']);
                $group = MemberGroup::query()->firstOrCreate(
                    ['slug' => Str::slug($groupName)],
                    ['name' => $groupName, 'is_active' => true]
                );

                MemberGroupAssignment::query()->create([
                    'member_id' => $member->id,
                    'member_group_id' => $group->id,
                    'assigned_at' => now()->toDateString(),
                    'assigned_by' => $actor?->id,
                ]);
            }

            if ($submission !== null) {
                $submission->update([
                    'member_id' => $member->id,
                    'normalized_payload' => $validatedData,
                    'sync_status' => $isDuplicateFlagged ? 'duplicate_flagged' : 'imported',
                    'duplicate_candidate_ids' => $isDuplicateFlagged ? $duplicateIds : null,
                    'processed_at' => now(),
                ]);
            }

            $this->auditLogger->log(
                event: 'members.pending_created',
                category: 'membership',
                description: "Created pending membership application for [{$member->fullName()}].",
                auditable: $member,
                newValues: [
                    'status' => $member->status,
                    'is_duplicate_flagged' => $isDuplicateFlagged,
                    'duplicate_candidate_ids' => $duplicateIds,
                ],
                actor: $actor,
            );

            return $member;
        });

        // Onboarding acknowledgement notice (post-commit fail-safe)
        $this->notificationOrchestrator->dispatch(new DispatchNotificationData(
            idempotencyKey: "membership_received:member:{$member->id}",
            templateCode: 'membership_received',
            recipient: new NotificationRecipient(
                memberId: (int) $member->id,
                name: $member->fullName(),
                email: $member->email,
                phone: $member->phone,
            ),
            variables: [
                'applicant_name' => $member->first_name,
            ],
            channels: ['sms', 'email'],
            eventName: 'membership.application_received',
            relatedType: $member->getMorphClass(),
            relatedId: (int) $member->id,
            initiatedByUserId: $actor?->id,
            failSafely: true,
        ));

        return $member;
    }
}
