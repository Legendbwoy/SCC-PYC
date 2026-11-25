<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Integrations\Models\IntegrationConnection;
use App\Modules\Integrations\Models\SyncLog;
use App\Modules\Membership\Models\Member;
use App\Modules\Membership\Services\MemberApprovalService;
use App\Modules\Membership\Services\MemberInvitationService;
use App\Modules\Membership\Services\MemberRegistrationService;
use App\Modules\Membership\Services\MemberSyncService;
use App\Modules\Membership\Services\MemberValidationService;
use App\Support\Audit\AuditLogger;
use App\Support\Security\PycPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Membership & Google Sync REST API v1 endpoints (Spec Section 13.2).
 */
class MembershipApiController extends Controller
{
    public function __construct(
        private readonly MemberValidationService $validationService,
        private readonly MemberRegistrationService $registrationService,
        private readonly MemberApprovalService $approvalService,
        private readonly MemberInvitationService $invitationService,
        private readonly MemberSyncService $syncService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Member::class);

        $query = Member::query()->with(['groups', 'account'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->boolean('duplicates_only')) {
            $query->where('is_duplicate_flagged', true);
        }

        if ($request->filled('q')) {
            $term = $request->string('q')->trim()->toString();
            $query->where(static function ($q) use ($term): void {
                $q->where('member_number', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        return response()->json([
            'success' => true,
            'message' => 'Members retrieved.',
            'data' => $query->paginate(20),
        ]);
    }

    public function show(Member $member): JsonResponse
    {
        Gate::authorize('view', $member);

        return response()->json([
            'success' => true,
            'message' => 'Member details retrieved.',
            'data' => $member->load(['groups', 'approvals', 'statusHistories', 'account']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Member::class);

        /** @var User $actor */
        $actor = $request->user();
        $validated = $this->validationService->validate($request->all());
        $member = $this->registrationService->createPending($validated, null, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Pending membership application created.',
            'data' => $member,
        ], 201);
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        Gate::authorize('update', $member);

        /** @var User $actor */
        $actor = $request->user();

        $payload = array_merge([
            'first_name' => $member->first_name,
            'middle_name' => $member->middle_name,
            'last_name' => $member->last_name,
            'phone' => $member->phone,
            'email' => $member->email,
            'date_of_birth' => $member->date_of_birth?->format('Y-m-d'),
            'gender' => $member->gender,
            'address' => $member->address,
            'occupation' => $member->occupation,
            'emergency_contact_name' => $member->emergency_contact_name,
            'emergency_contact_phone' => $member->emergency_contact_phone,
        ], $request->all());

        $validated = $this->validationService->validate($payload);
        unset($validated['group_name']);

        $oldValues = $member->only(array_keys($validated));
        $member->update($validated);

        $this->auditLogger->log(
            event: 'members.updated',
            category: 'membership',
            description: "Updated member profile for [{$member->fullName()}].",
            auditable: $member,
            oldValues: $oldValues,
            newValues: $validated,
            actor: $actor,
        );

        return response()->json([
            'success' => true,
            'message' => 'Member updated.',
            'data' => $member->fresh(),
        ]);
    }

    public function approve(Request $request, Member $member): JsonResponse
    {
        Gate::authorize('approve', $member);

        /** @var User $reviewer */
        $reviewer = $request->user();
        $notes = $request->string('reason')->trim()->toString() ?: null;

        $approved = $this->approvalService->approve($member, $reviewer, $notes, true);

        return response()->json([
            'success' => true,
            'message' => 'Membership approved and portal invitation queued.',
            'data' => $approved,
        ]);
    }

    public function reject(Request $request, Member $member): JsonResponse
    {
        Gate::authorize('approve', $member);

        /** @var User $reviewer */
        $reviewer = $request->user();
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $rejected = $this->approvalService->reject($member, $reviewer, $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => 'Membership application rejected.',
            'data' => $rejected,
        ]);
    }

    public function invite(Request $request, Member $member): JsonResponse
    {
        Gate::authorize('approve', $member);

        /** @var User $actor */
        $actor = $request->user();
        $result = $this->invitationService->invite($member, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Portal invitation dispatched.',
            'data' => [
                'invitation_id' => $result['invitation']->id,
                'expires_at' => $result['invitation']->expires_at->toIso8601String(),
            ],
        ]);
    }

    public function googleStatus(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::GoogleSyncView->value), 403);

        $connection = IntegrationConnection::query()->where('provider', 'google_sheets')->first();
        $latestLog = SyncLog::query()->where('provider', 'google_sheets')->latest('id')->first();

        return response()->json([
            'success' => true,
            'message' => 'Google Sheets integration status retrieved.',
            'data' => [
                'spreadsheet_configured' => trim((string) ($connection?->spreadsheet_id ?: config('pyc.membership.google_sheets.spreadsheet_id', ''))) !== '',
                'connection' => $connection,
                'latest_sync' => $latestLog,
            ],
        ]);
    }

    public function googleSync(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::GoogleSyncRun->value), 403);

        /** @var User $actor */
        $actor = $request->user();
        $syncLog = $this->syncService->sync($actor, 'manual');

        return response()->json([
            'success' => $syncLog->status !== 'failed',
            'message' => "Synchronization finished with status: {$syncLog->status}.",
            'data' => $syncLog,
        ], $syncLog->status === 'failed' ? 422 : 200);
    }

    public function googleLogs(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::GoogleSyncView->value), 403);

        return response()->json([
            'success' => true,
            'message' => 'Google synchronization logs retrieved.',
            'data' => SyncLog::query()->where('provider', 'google_sheets')->latest('id')->paginate(20),
        ]);
    }
}
