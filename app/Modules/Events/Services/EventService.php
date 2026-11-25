<?php

declare(strict_types=1);

namespace App\Modules\Events\Services;

use App\Models\User;
use App\Modules\Contributions\Enums\ObligationStatus;
use App\Modules\Contributions\Models\ContributionType;
use App\Modules\Contributions\Models\MemberObligation;
use App\Modules\Contributions\Services\ContributionCampaignService;
use App\Modules\Events\Contracts\EventRegistrationServiceInterface;
use App\Modules\Events\Models\EventRegistration;
use App\Modules\Events\Models\PycEvent;
use App\Modules\Membership\Enums\MemberStatus;
use App\Modules\Membership\Models\Member;
use App\Modules\Notifications\DTOs\DispatchNotificationData;
use App\Modules\Notifications\DTOs\NotificationRecipient;
use App\Modules\Notifications\Services\NotificationOrchestrator;
use App\Support\Audit\AuditLogger;
use App\Support\Shared\ReferenceGenerator;
use App\Support\Shared\ReferenceType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Coordinates PYC event publishing, registrations, attendance, notifications,
 * and shared contribution campaign integration (Spec Section 9.1).
 */
class EventService implements EventRegistrationServiceInterface
{
    public function __construct(
        private readonly ReferenceGenerator $referenceGenerator,
        private readonly ContributionCampaignService $campaignService,
        private readonly NotificationOrchestrator $notificationOrchestrator,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createEvent(array $data, User $actor, int $contributionPesewas = 0): PycEvent
    {
        $startsAt = Carbon::parse((string) $data['starts_at']);

        return DB::transaction(function () use ($data, $startsAt, $actor, $contributionPesewas): PycEvent {
            $reference = $this->referenceGenerator->next(ReferenceType::Event, (int) $startsAt->year);
            $campaignId = $data['contribution_campaign_id'] ?? null;

            if ($campaignId === null && $contributionPesewas > 0) {
                $eventType = ContributionType::query()->where('code', 'event_contribution')->firstOrFail();
                $campaign = $this->campaignService->createCampaign([
                    'contribution_type_id' => $eventType->id,
                    'code' => strtolower($reference),
                    'title' => trim((string) $data['title']).' Contribution',
                    'amount_pesewas' => $contributionPesewas,
                    'frequency' => 'one_off',
                    'effective_from' => now()->toDateString(),
                    'effective_to' => $startsAt->toDateString(),
                ], $actor, false);

                $campaignId = $campaign->id;
            }

            $event = PycEvent::query()->create([
                'reference' => $reference,
                'title' => trim((string) $data['title']),
                'description' => $data['description'] ?? null,
                'venue' => trim((string) $data['venue']),
                'starts_at' => $startsAt,
                'ends_at' => ! empty($data['ends_at']) ? Carbon::parse((string) $data['ends_at']) : null,
                'registration_deadline' => ! empty($data['registration_deadline']) ? Carbon::parse((string) $data['registration_deadline']) : null,
                'capacity' => ! empty($data['capacity']) ? (int) $data['capacity'] : null,
                'allow_registration' => (bool) ($data['allow_registration'] ?? true),
                'allow_cancellation' => (bool) ($data['allow_cancellation'] ?? true),
                'contribution_campaign_id' => $campaignId,
                'status' => (string) ($data['status'] ?? 'published'),
                'created_by' => $actor->id,
            ]);

            $this->auditLogger->log(
                event: 'events.created',
                category: 'events',
                description: "Created event [{$event->reference}] ({$event->title}).",
                auditable: $event,
                actor: $actor,
            );

            return $event;
        });
    }

    public function publishAndAnnounce(PycEvent $event, User $actor): int
    {
        $event->update([
            'status' => 'published',
            'announced_at' => now(),
        ]);

        $members = Member::query()->with('account')->where('status', MemberStatus::Active->value)->get();
        $dateFormatted = $event->starts_at->timezone('Africa/Accra')->format('d M Y, H:i');
        $dispatched = 0;

        foreach ($members as $member) {
            $log = $this->notificationOrchestrator->dispatch(new DispatchNotificationData(
                idempotencyKey: "event_announcement:{$event->id}:member:{$member->id}",
                templateCode: 'event_announcement',
                recipient: new NotificationRecipient(
                    userId: $member->account?->user_id,
                    memberId: (int) $member->id,
                    name: $member->fullName(),
                    email: $member->email,
                    phone: $member->phone,
                ),
                variables: [
                    'event_title' => $event->title,
                    'event_date' => $dateFormatted,
                    'venue' => $event->venue,
                ],
                channels: ['sms', 'email'],
                initiatedByUserId: (int) $actor->id,
                priorityQueue: (string) config('notifications.queues.bulk', 'bulk_notifications'),
                failSafely: true,
            ));

            if ($log !== null && $log->status !== 'suppressed') {
                $dispatched++;
            }
        }

        $this->auditLogger->log(
            event: 'events.announced',
            category: 'events',
            description: "Published and announced event [{$event->reference}] to {$dispatched} consenting members.",
            auditable: $event,
            actor: $actor,
        );

        return $dispatched;
    }

    public function sendReminder(PycEvent $event, User $actor): int
    {
        $registrations = EventRegistration::query()
            ->with('member.account')
            ->where('event_id', $event->id)
            ->where('status', 'registered')
            ->get();

        $dateFormatted = $event->starts_at->timezone('Africa/Accra')->format('d M Y, H:i');
        $sentCount = 0;

        foreach ($registrations as $reg) {
            if ($reg->member === null) {
                continue;
            }

            $this->notificationOrchestrator->dispatch(new DispatchNotificationData(
                idempotencyKey: "event_reminder:{$event->id}:reg:{$reg->id}:".now()->format('YmdH'),
                templateCode: 'event_reminder',
                recipient: new NotificationRecipient(
                    userId: $reg->member->account?->user_id,
                    memberId: (int) $reg->member->id,
                    name: $reg->member->fullName(),
                    email: $reg->member->email,
                    phone: $reg->member->phone,
                ),
                variables: [
                    'event_title' => $event->title,
                    'event_date' => $dateFormatted,
                    'venue' => $event->venue,
                ],
                channels: ['sms', 'email'],
                initiatedByUserId: (int) $actor->id,
                failSafely: true,
            ));
            $sentCount++;
        }

        return $sentCount;
    }

    public function cancelEvent(PycEvent $event, string $reason, User $actor): PycEvent
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A cancellation reason is required.');
        }

        $registrations = EventRegistration::query()
            ->with('member.account')
            ->where('event_id', $event->id)
            ->where('status', 'registered')
            ->get();

        DB::transaction(function () use ($event, $reason, $actor): void {
            $event->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
            ]);

            $this->auditLogger->log(
                event: 'events.cancelled',
                category: 'events',
                description: "Cancelled event [{$event->reference}]: {$reason}",
                auditable: $event,
                actor: $actor,
            );
        });

        $dateFormatted = $event->starts_at->timezone('Africa/Accra')->format('d M Y');

        foreach ($registrations as $reg) {
            if ($reg->member === null) {
                continue;
            }

            $this->notificationOrchestrator->dispatch(new DispatchNotificationData(
                idempotencyKey: "event_cancelled:{$event->id}:member:{$reg->member->id}",
                templateCode: 'event_cancelled',
                recipient: new NotificationRecipient(
                    userId: $reg->member->account?->user_id,
                    memberId: (int) $reg->member->id,
                    name: $reg->member->fullName(),
                    email: $reg->member->email,
                    phone: $reg->member->phone,
                ),
                variables: [
                    'event_title' => $event->title,
                    'event_date' => $dateFormatted,
                    'reason' => $reason,
                ],
                channels: ['sms', 'email'],
                initiatedByUserId: (int) $actor->id,
                failSafely: true,
            ));
        }

        return $event->fresh() ?? $event;
    }

    public function registerMember(int $eventId, int $memberId): void
    {
        [$event, $member, $registration] = DB::transaction(function () use ($eventId, $memberId): array {
            $event = PycEvent::query()->with('contributionCampaign')->lockForUpdate()->findOrFail($eventId);
            $member = Member::query()->with('account')->findOrFail($memberId);

            if ($event->status !== 'published' || ! $event->allow_registration) {
                throw new InvalidArgumentException('This event is not open for registration.');
            }

            if ($event->registration_deadline !== null && $event->registration_deadline->isPast()) {
                throw new InvalidArgumentException('The registration deadline for this event has passed.');
            }

            if ($event->capacity !== null) {
                $activeCount = EventRegistration::query()
                    ->where('event_id', $event->id)
                    ->whereIn('status', ['registered', 'attended'])
                    ->where('member_id', '!=', $member->id)
                    ->count();

                if ($activeCount >= $event->capacity) {
                    throw new InvalidArgumentException('This event has reached its maximum registration capacity.');
                }
            }

            $obligationId = null;

            // Reuse shared contribution campaign & obligation engine (Spec Section 9.1)
            if ($event->contributionCampaign !== null) {
                $obligation = MemberObligation::query()->firstOrCreate(
                    [
                        'member_id' => $member->id,
                        'contribution_campaign_id' => $event->contributionCampaign->id,
                        'period_key' => $event->reference,
                    ],
                    [
                        'period_start' => now()->toDateString(),
                        'period_end' => $event->starts_at->toDateString(),
                        'due_date' => $event->starts_at->toDateString(),
                        'amount_due_pesewas' => $event->contributionCampaign->amount_pesewas,
                        'currency' => $event->contributionCampaign->currency,
                        'status' => ObligationStatus::Unpaid->value,
                    ]
                );

                // If previously waived due to cancellation and re-registering, restore unpaid state
                if ($obligation->status === ObligationStatus::Waived->value && $obligation->amount_allocated_pesewas === 0) {
                    $obligation->update([
                        'adjustment_pesewas' => 0,
                        'status' => ObligationStatus::Unpaid->value,
                    ]);
                }

                $obligationId = $obligation->id;
            }

            $registration = EventRegistration::query()->updateOrCreate(
                [
                    'event_id' => $event->id,
                    'member_id' => $member->id,
                ],
                [
                    'member_obligation_id' => $obligationId,
                    'status' => 'registered',
                    'registered_at' => now(),
                    'cancelled_at' => null,
                    'cancellation_reason' => null,
                ]
            );

            return [$event, $member, $registration];
        });

        $this->notificationOrchestrator->dispatch(new DispatchNotificationData(
            idempotencyKey: "event_registration:{$registration->id}:".$registration->registered_at?->timestamp,
            templateCode: 'event_registration',
            recipient: new NotificationRecipient(
                userId: $member->account?->user_id,
                memberId: (int) $member->id,
                name: $member->fullName(),
                email: $member->email,
                phone: $member->phone,
            ),
            variables: [
                'member_name' => $member->first_name,
                'event_title' => $event->title,
                'event_date' => $event->starts_at->timezone('Africa/Accra')->format('d M Y, H:i'),
                'venue' => $event->venue,
            ],
            channels: ['sms', 'email'],
            failSafely: true,
        ));
    }

    public function cancelRegistration(int $eventId, int $memberId, ?string $reason = null): void
    {
        DB::transaction(function () use ($eventId, $memberId, $reason): void {
            $event = PycEvent::query()->findOrFail($eventId);

            if (! $event->allow_cancellation) {
                throw new InvalidArgumentException('Cancellations are not permitted for this event.');
            }

            $reg = EventRegistration::query()
                ->with('obligation')
                ->where('event_id', $event->id)
                ->where('member_id', $memberId)
                ->lockForUpdate()
                ->firstOrFail();

            $reg->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason ?: 'Cancelled by member',
            ]);

            if ($reg->obligation !== null && $reg->obligation->amount_allocated_pesewas === 0) {
                $reg->obligation->update([
                    'adjustment_pesewas' => -1 * $reg->obligation->amount_due_pesewas,
                    'status' => ObligationStatus::Waived->value,
                ]);
            }
        });
    }

    public function markAttendance(EventRegistration $registration, bool $attended, User $actor): EventRegistration
    {
        $registration->update([
            'status' => $attended ? 'attended' : 'absent',
            'attended_at' => $attended ? now() : null,
            'recorded_by' => $actor->id,
        ]);

        $this->auditLogger->log(
            event: 'events.attendance_marked',
            category: 'events',
            description: "Marked registration #{$registration->id} as {$registration->status}.",
            auditable: $registration,
            actor: $actor,
        );

        return $registration;
    }
}
