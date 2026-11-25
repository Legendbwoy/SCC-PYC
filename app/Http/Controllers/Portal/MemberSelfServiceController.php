<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Contributions\Services\OutstandingBalanceService;
use App\Modules\Events\Models\PycEvent;
use App\Modules\Events\Services\EventService;
use App\Modules\Notifications\Services\RecipientResolver;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Services\PaymentService;
use App\Support\Audit\AuditLogger;
use App\Support\Shared\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MemberSelfServiceController extends Controller
{
    public function __construct(
        private readonly OutstandingBalanceService $balanceService,
        private readonly PaymentService $paymentService,
        private readonly EventService $eventService,
        private readonly RecipientResolver $recipientResolver,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function profile(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $member = $user->memberAccount?->member()->with(['groups', 'statusHistories'])->first();

        return view('portal.member.profile', [
            'user' => $user,
            'member' => $member,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $member = $user->memberAccount?->member;

        abort_if($member === null, 403, 'No linked PYC membership record is attached to this account.');

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:120'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:32'],
        ]);

        $normalizedPhone = ! empty($validated['phone'])
            ? $this->recipientResolver->normalizePhone($validated['phone'])
            : null;

        if (! empty($validated['phone']) && $normalizedPhone === null) {
            return back()->withErrors(['phone' => 'Please enter a valid Ghana or international phone number.'])->withInput();
        }

        $oldValues = $member->only(['phone', 'address', 'occupation', 'emergency_contact_name', 'emergency_contact_phone']);

        $updates = [
            'phone' => $normalizedPhone ?? $member->phone,
            'address' => $validated['address'] ?? null,
            'occupation' => $validated['occupation'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => ! empty($validated['emergency_contact_phone'])
                ? ($this->recipientResolver->normalizePhone($validated['emergency_contact_phone']) ?? $validated['emergency_contact_phone'])
                : null,
        ];

        $member->update($updates);

        if ($normalizedPhone !== null) {
            $user->update(['phone' => $normalizedPhone]);
        }

        $this->auditLogger->log(
            event: 'members.self_profile_updated',
            category: 'membership',
            description: "Member [{$member->member_number}] updated their personal contact details.",
            auditable: $member,
            oldValues: $oldValues,
            newValues: $updates,
            actor: $user,
        );

        return redirect()
            ->route('member.profile')
            ->with('status', 'Your membership contact profile has been updated.');
    }

    public function dues(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $member = $user->memberAccount?->member;

        $statement = $member !== null
            ? $this->balanceService->memberStatement($member)
            : null;

        return view('portal.member.dues', [
            'user' => $user,
            'member' => $member,
            'statement' => $statement,
        ]);
    }

    public function payments(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $member = $user->memberAccount?->member;

        $payments = $member !== null
            ? Payment::query()->with('receipt')->where('member_id', $member->id)->latest('id')->paginate(15)
            : collect();

        $statement = $member !== null ? $this->balanceService->memberStatement($member) : null;

        return view('portal.member.payments', [
            'user' => $user,
            'member' => $member,
            'payments' => $payments,
            'statement' => $statement,
        ]);
    }

    public function initializePayment(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $member = $user->memberAccount?->member;
        abort_if($member === null, 403);

        $validated = $request->validate([
            'amount_ghs' => ['required', 'string', 'regex:/^\d+(?:\.\d{1,2})?$/'],
        ]);

        $money = Money::fromMajorString($validated['amount_ghs'], 'GHS');

        $checkout = $this->paymentService->initializeOnlinePayment(
            member: $member,
            amountPesewas: $money->pesewas,
            actor: $user,
        );

        return redirect()->away($checkout['authorization_url']);
    }

    public function paymentCallback(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $reference = $request->string('reference')->trim()->toString();

        if ($reference === '') {
            return redirect()->route('member.payments');
        }

        try {
            $verified = $this->paymentService->verifyOnlinePayment($reference, $user);

            return redirect()
                ->route('member.payments')
                ->with('status', "Payment {$verified->reference} confirmed! Official Receipt {$verified->receipt?->receipt_number} has been issued.");
        } catch (\Throwable $e) {
            return redirect()
                ->route('member.payments')
                ->withErrors(['payment' => 'Could not verify online payment automatically: '.$e->getMessage()]);
        }
    }

    public function events(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $member = $user->memberAccount?->member;

        $events = PycEvent::query()
            ->with(['contributionCampaign', 'registrations' => static function ($q) use ($member): void {
                if ($member !== null) {
                    $q->where('member_id', $member->id);
                } else {
                    $q->whereRaw('1 = 0');
                }
            }])
            ->where('status', 'published')
            ->orderBy('starts_at')
            ->get();

        return view('portal.member.events', [
            'member' => $member,
            'events' => $events,
        ]);
    }

    public function registerEvent(Request $request, PycEvent $event): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $member = $user->memberAccount?->member;
        abort_if($member === null, 403);

        $this->eventService->registerMember((int) $event->id, (int) $member->id);

        return redirect()
            ->route('member.events')
            ->with('status', "You are registered for {$event->title}!");
    }

    public function cancelEventRegistration(Request $request, PycEvent $event): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $member = $user->memberAccount?->member;
        abort_if($member === null, 403);

        $this->eventService->cancelRegistration((int) $event->id, (int) $member->id, 'Cancelled via Member Portal');

        return redirect()
            ->route('member.events')
            ->with('status', "Your registration for {$event->title} has been cancelled.");
    }
}
