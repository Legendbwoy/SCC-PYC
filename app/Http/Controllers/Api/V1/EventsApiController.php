<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Events\Models\EventRegistration;
use App\Modules\Events\Models\PycEvent;
use App\Modules\Events\Services\EventService;
use App\Support\Security\PycPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventsApiController extends Controller
{
    public function __construct(
        private readonly EventService $eventService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = PycEvent::query()
            ->with('contributionCampaign')
            ->withCount(['registrations' => static fn ($q) => $q->whereIn('status', ['registered', 'attended'])])
            ->orderByDesc('starts_at');

        if (! $user->isExecutiveOfficer()) {
            $query->where('status', 'published');
        }

        return response()->json([
            'success' => true,
            'message' => 'Events retrieved.',
            'data' => $query->paginate(20),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->isExecutiveOfficer() && $actor->can(PycPermission::NotificationsSend->value), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'venue' => ['required', 'string', 'max:180'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'registration_deadline' => ['nullable', 'date'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'allow_registration' => ['boolean'],
            'allow_cancellation' => ['boolean'],
            'contribution_amount_pesewas' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string'],
        ]);

        $event = $this->eventService->createEvent(
            data: $validated,
            actor: $actor,
            contributionPesewas: (int) ($validated['contribution_amount_pesewas'] ?? 0),
        );

        return response()->json([
            'success' => true,
            'message' => 'Event created.',
            'data' => $event->load('contributionCampaign'),
        ], 201);
    }

    public function show(PycEvent $event): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Event details retrieved.',
            'data' => $event->load(['contributionCampaign', 'registrations.member']),
        ]);
    }

    public function announce(Request $request, PycEvent $event): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can(PycPermission::NotificationsSendBulk->value) || $actor->can(PycPermission::NotificationsSend->value), 403);

        $count = $this->eventService->publishAndAnnounce($event, $actor);

        return response()->json([
            'success' => true,
            'message' => "Event published and announced to {$count} consenting members.",
            'data' => $event->fresh(),
        ]);
    }

    public function cancel(Request $request, PycEvent $event): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->isExecutiveOfficer() && $actor->can(PycPermission::NotificationsSend->value), 403);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $cancelled = $this->eventService->cancelEvent($event, $validated['reason'], $actor);

        return response()->json([
            'success' => true,
            'message' => 'Event cancelled and registered members notified.',
            'data' => $cancelled,
        ]);
    }

    public function register(Request $request, PycEvent $event): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($request->filled('member_id') && $user->can(PycPermission::MembersUpdate->value)) {
            $memberId = $request->integer('member_id');
        } else {
            $memberId = (int) ($user->memberAccount?->member_id ?? 0);
            abort_if($memberId === 0, 422, 'No linked member record found for registration.');
        }

        $this->eventService->registerMember((int) $event->id, $memberId);

        return response()->json([
            'success' => true,
            'message' => 'Event registration confirmed.',
        ], 201);
    }

    public function cancelRegistration(Request $request, PycEvent $event): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $member = $user->memberAccount?->member;
        abort_if($member === null, 422);

        $this->eventService->cancelRegistration(
            (int) $event->id,
            (int) $member->id,
            $request->string('reason')->toString() ?: null
        );

        return response()->json([
            'success' => true,
            'message' => 'Event registration cancelled.',
        ]);
    }

    public function markAttendance(Request $request, EventRegistration $registration): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can(PycPermission::MembersUpdate->value), 403);

        $updated = $this->eventService->markAttendance($registration, $request->boolean('attended', true), $actor);

        return response()->json([
            'success' => true,
            'message' => 'Attendance updated.',
            'data' => $updated,
        ]);
    }
}
