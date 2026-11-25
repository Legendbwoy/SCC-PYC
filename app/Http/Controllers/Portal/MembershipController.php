<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Membership\Models\Member;
use App\Modules\Membership\Models\MemberGroup;
use App\Modules\Membership\Models\MemberGroupAssignment;
use App\Modules\Membership\Services\MemberApprovalService;
use App\Modules\Membership\Services\MemberInvitationService;
use App\Modules\Membership\Services\MemberRegistrationService;
use App\Modules\Membership\Services\MemberValidationService;
use App\Support\Audit\AuditLogger;
use App\Support\Media\MediaManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

class MembershipController extends Controller
{
    public function __construct(
        private readonly MemberValidationService $validationService,
        private readonly MemberRegistrationService $registrationService,
        private readonly MemberApprovalService $approvalService,
        private readonly MemberInvitationService $invitationService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Member::class);

        $status = $request->string('status')->trim()->toString();
        $search = $request->string('q')->trim()->toString();
        $groupId = $request->integer('group_id');
        $duplicatesOnly = $request->boolean('duplicates_only');

        $query = Member::query()->with(['groups', 'account'])->latest('id');

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($duplicatesOnly) {
            $query->where('is_duplicate_flagged', true);
        }

        if ($groupId > 0) {
            $query->whereHas('groups', static fn ($q) => $q->where('member_groups.id', $groupId));
        }

        if ($search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->where('member_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $counts = Member::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return view('portal.membership.index', [
            'members' => $query->paginate(20)->withQueryString(),
            'counts' => $counts,
            'duplicateCount' => Member::query()->where('is_duplicate_flagged', true)->where('status', 'pending')->count(),
            'groups' => MemberGroup::query()->where('is_active', true)->orderBy('name')->get(),
            'filters' => [
                'status' => $status,
                'q' => $search,
                'group_id' => $groupId,
                'duplicates_only' => $duplicatesOnly,
            ],
        ]);
    }

    public function show(Member $member): View
    {
        Gate::authorize('view', $member);

        $member->load([
            'submissions',
            'approvals.reviewer',
            'statusHistories.actor',
            'groups',
            'account.user',
            'invitations.inviter',
        ]);

        $duplicateCandidates = ! empty($member->duplicate_candidate_ids)
            ? Member::query()->whereIn('id', (array) $member->duplicate_candidate_ids)->get()
            : collect();

        return view('portal.membership.show', [
            'member' => $member,
            'duplicateCandidates' => $duplicateCandidates,
            'availableGroups' => MemberGroup::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Member::class);

        /** @var User $actor */
        $actor = $request->user();
        $validated = $this->validationService->validate($request->all());
        $member = $this->registrationService->createPending($validated, null, $actor);

        return redirect()
            ->route('membership.show', $member)
            ->with('status', "Pending membership application recorded for {$member->fullName()}.");
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('update', $member);

        /** @var User $actor */
        $actor = $request->user();
        $validated = $this->validationService->validate($request->all());
        unset($validated['group_name']);

        $old = $member->only(array_keys($validated));
        $member->update($validated);

        if ($request->filled('member_group_id')) {
            MemberGroupAssignment::query()->firstOrCreate(
                [
                    'member_id' => $member->id,
                    'member_group_id' => $request->integer('member_group_id'),
                    'ended_at' => null,
                ],
                [
                    'assigned_at' => now()->toDateString(),
                    'assigned_by' => $actor->id,
                ]
            );
        }

        $this->auditLogger->log(
            event: 'members.updated',
            category: 'membership',
            description: "Updated member profile for [{$member->fullName()}].",
            auditable: $member,
            oldValues: $old,
            newValues: $validated,
            actor: $actor,
        );

        return redirect()
            ->route('membership.show', $member)
            ->with('status', 'Member details updated.');
    }

    public function approve(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('approve', $member);

        /** @var User $reviewer */
        $reviewer = $request->user();
        $notes = $request->string('reason')->trim()->toString() ?: null;

        $approved = $this->approvalService->approve($member, $reviewer, $notes, true);

        return redirect()
            ->route('membership.show', $approved)
            ->with('status', "Approved {$approved->fullName()} with Member Number {$approved->member_number} and queued portal invitation.");
    }

    public function reject(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('approve', $member);

        /** @var User $reviewer */
        $reviewer = $request->user();
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->approvalService->reject($member, $reviewer, $validated['reason']);

        return redirect()
            ->route('membership.show', $member)
            ->with('status', 'Membership application marked as rejected.');
    }

    public function archive(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('archive', $member);

        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->approvalService->archive($member, $actor, $validated['reason']);

        return redirect()
            ->route('membership.show', $member)
            ->with('status', 'Member archived and linked portal account suspended.');
    }

    public function invite(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('approve', $member);

        /** @var User $actor */
        $actor = $request->user();
        $this->invitationService->invite($member, $actor);

        return redirect()
            ->route('membership.show', $member)
            ->with('status', 'New single-use portal invitation sent.');
    }

    public function updatePhoto(Request $request, Member $member, MediaManagementService $mediaService): RedirectResponse
    {
        Gate::authorize('update', $member);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        /** @var User $actor */
        $actor = $request->user();
        /** @var UploadedFile $file */
        $file = $request->file('photo');

        $mediaService->updateMemberPhoto($member, $file, $actor);

        return redirect()
            ->route('membership.show', $member)
            ->with('status', "Updated member portrait photo for {$member->fullName()}.");
    }
}
