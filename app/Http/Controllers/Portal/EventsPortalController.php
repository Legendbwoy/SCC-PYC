<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Events\Models\EventRegistration;
use App\Modules\Events\Models\PycEvent;
use App\Modules\Events\Services\EventService;
use App\Support\Security\PycPermission;
use App\Support\Shared\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EventsPortalController extends Controller
{
    public function __construct(
        private readonly EventService $eventService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isExecutiveOfficer(), 403);

        return view('portal.events.index', [
            'events' => PycEvent::query()
                ->with('contributionCampaign')
                ->withCount([
                    'registrations as registered_count' => static fn ($q) => $q->whereIn('status', ['registered', 'attended']),
                    'registrations as attended_count' => static fn ($q) => $q->where('status', 'attended'),
                ])
                ->orderByDesc('starts_at')
                ->paginate(15),
        ]);
    }

    public function show(Request $request, PycEvent $event): View
    {
        abort_unless($request->user()?->isExecutiveOfficer(), 403);

        $event->load(['contributionCampaign', 'registrations.member', 'registrations.obligation']);

        return view('portal.events.show', [
            'event' => $event,
        ]);
    }

    public function store(Request $request): RedirectResponse
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
            'capacity' => ['nullable', 'integer', 'min:1'],
            'contribution_ghs' => ['nullable', 'string', 'regex:/^\d+(?:\.\d{1,2})?$/'],
        ]);

        $pesewas = ! empty($validated['contribution_ghs'])
            ? Money::fromMajorString($validated['contribution_ghs'], 'GHS')->pesewas
            : 0;

        $event = $this->eventService->createEvent($validated, $actor, $pesewas);

        return redirect()
            ->route('events.show', $event)
            ->with('status', "Created event {$event->reference} ({$event->title}).");
    }

    public function announce(Request $request, PycEvent $event): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can(PycPermission::NotificationsSendBulk->value) || $actor->can(PycPermission::NotificationsSend->value), 403);

        $count = $this->eventService->publishAndAnnounce($event, $actor);

        return back()->with('status', "Published and announced event to {$count} consenting members.");
    }

    public function remind(Request $request, PycEvent $event): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can(PycPermission::NotificationsSend->value), 403);

        $count = $this->eventService->sendReminder($event, $actor);

        return back()->with('status', "Queued event reminder for {$count} registered participants.");
    }

    public function cancel(Request $request, PycEvent $event): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->isExecutiveOfficer() && $actor->can(PycPermission::NotificationsSend->value), 403);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->eventService->cancelEvent($event, $validated['reason'], $actor);

        return back()->with('status', 'Event cancelled and registered participants notified.');
    }

    public function markAttendance(Request $request, EventRegistration $registration): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can(PycPermission::MembersUpdate->value), 403);

        $this->eventService->markAttendance($registration, $request->boolean('attended', true), $actor);

        return back()->with('status', 'Participant attendance recorded.');
    }
}
