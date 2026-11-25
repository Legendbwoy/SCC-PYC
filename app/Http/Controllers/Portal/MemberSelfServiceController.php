<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Contributions\Services\OutstandingBalanceService;
use App\Modules\Notifications\Services\RecipientResolver;
use App\Support\Audit\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sprint 3 Member Portal Self-Service Profile & Personal Dues Statements (Spec Section 3.5 & 16).
 */
class MemberSelfServiceController extends Controller
{
    public function __construct(
        private readonly OutstandingBalanceService $balanceService,
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
}
