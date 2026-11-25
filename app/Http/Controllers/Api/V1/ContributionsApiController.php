<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Contributions\Models\ContributionCampaign;
use App\Modules\Contributions\Models\ContributionType;
use App\Modules\Contributions\Models\MemberObligation;
use App\Modules\Contributions\Services\ContributionCampaignService;
use App\Modules\Contributions\Services\MonthlyDuesService;
use App\Modules\Contributions\Services\ObligationAdjustmentService;
use App\Modules\Contributions\Services\OutstandingBalanceService;
use App\Modules\Membership\Models\Member;
use App\Support\Security\PycPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Contribution & Dues REST API v1 endpoints (Spec Section 13.3).
 */
class ContributionsApiController extends Controller
{
    public function __construct(
        private readonly ContributionCampaignService $campaignService,
        private readonly MonthlyDuesService $monthlyDuesService,
        private readonly ObligationAdjustmentService $adjustmentService,
        private readonly OutstandingBalanceService $balanceService,
    ) {}

    public function types(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::DuesView->value), 403);

        return response()->json([
            'success' => true,
            'message' => 'Contribution types retrieved.',
            'data' => ContributionType::query()->where('is_active', true)->orderBy('id')->get(),
        ]);
    }

    public function campaigns(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::DuesView->value), 403);

        return response()->json([
            'success' => true,
            'message' => 'Contribution campaigns retrieved.',
            'data' => ContributionCampaign::query()->with('type')->latest('id')->paginate(20),
        ]);
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::DuesGenerate->value), 403);

        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'contribution_type_id' => ['required', 'integer', 'exists:contribution_types,id'],
            'code' => ['nullable', 'string', 'max:80', 'unique:contribution_campaigns,code'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount_pesewas' => ['required', 'integer', 'min:1'],
            'frequency' => ['required', 'string', Rule::in(['monthly', 'one_off', 'annual'])],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'due_day_of_month' => ['nullable', 'integer', 'min:1', 'max:28'],
            'allow_partial_payments' => ['boolean'],
            'allow_advance_payments' => ['boolean'],
            'generate_obligations_now' => ['boolean'],
        ]);

        $campaign = $this->campaignService->createCampaign(
            data: $validated,
            actor: $actor,
            generateOneOffObligations: (bool) ($validated['generate_obligations_now'] ?? false),
        );

        return response()->json([
            'success' => true,
            'message' => 'Contribution campaign created.',
            'data' => $campaign->load('type'),
        ], 201);
    }

    public function updateCampaign(Request $request, ContributionCampaign $campaign): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::DuesGenerate->value), 403);

        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'effective_to' => ['nullable', 'date'],
            'due_day_of_month' => ['sometimes', 'integer', 'min:1', 'max:28'],
            'allow_partial_payments' => ['sometimes', 'boolean'],
            'allow_advance_payments' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'paused', 'closed'])],
        ]);

        $updated = $this->campaignService->updateCampaign($campaign, $validated, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Contribution campaign updated.',
            'data' => $updated,
        ]);
    }

    public function generateDues(Request $request): JsonResponse
    {
        Gate::authorize('generate', MemberObligation::class);

        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'period' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $result = $this->monthlyDuesService->generate($validated['period'], $actor);

        return response()->json([
            'success' => true,
            'message' => "Monthly dues generated for {$result['period']}.",
            'data' => array_merge($result, [
                'currency' => 'GHS',
            ]),
        ]);
    }

    public function memberDues(Request $request, Member $member): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $canAccess = $user->can(PycPermission::DuesView->value)
            || $user->memberAccount()->where('member_id', $member->id)->exists();

        abort_unless($canAccess, 403);

        $statement = $this->balanceService->memberStatement($member);

        return response()->json([
            'success' => true,
            'message' => 'Member dues statement retrieved.',
            'data' => [
                'member_id' => $member->id,
                'member_number' => $member->member_number,
                'member_name' => $member->fullName(),
                'total_billed' => $statement['total_billed']->toApiArray(),
                'total_adjusted' => $statement['total_adjusted']->toApiArray(),
                'total_paid' => $statement['total_paid']->toApiArray(),
                'arrears_due' => $statement['arrears_due']->toApiArray(),
                'total_outstanding' => $statement['total_outstanding']->toApiArray(),
                'obligations' => $statement['obligations'],
            ],
        ]);
    }

    public function outstanding(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::DuesView->value), 403);

        $campaignId = $request->filled('campaign_id') ? $request->integer('campaign_id') : null;
        $totals = $this->balanceService->totals($campaignId);
        $arrears = $this->balanceService->arrearsSummary($campaignId)->map(static fn (array $row): array => [
            'member_id' => $row['member']->id,
            'member_number' => $row['member']->member_number,
            'member_name' => $row['member']->fullName(),
            'obligations_count' => $row['obligations_count'],
            'outstanding_pesewas' => $row['outstanding_pesewas'],
            'currency' => 'GHS',
            'formatted_amount' => $row['outstanding_money']->formattedAmount(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Operational outstanding balances retrieved.',
            'data' => [
                'summary' => [
                    'expected' => $totals['expected']->toApiArray(),
                    'adjusted' => $totals['adjusted']->toApiArray(),
                    'collected' => $totals['collected']->toApiArray(),
                    'outstanding' => $totals['outstanding']->toApiArray(),
                ],
                'members_in_arrears' => $arrears,
            ],
        ]);
    }

    public function storeAdjustment(Request $request, MemberObligation $obligation): JsonResponse
    {
        Gate::authorize('adjust', $obligation);

        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'adjustment_type' => ['required', 'string', Rule::in(['waiver', 'exemption', 'discount', 'surcharge'])],
            'amount_pesewas' => ['nullable', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $adjustment = $this->adjustmentService->applyAdjustment(
            obligation: $obligation,
            adjustmentType: $validated['adjustment_type'],
            amountPesewas: (int) ($validated['amount_pesewas'] ?? 0),
            reason: $validated['reason'],
            actor: $actor,
        );

        return response()->json([
            'success' => true,
            'message' => 'Obligation adjustment applied.',
            'data' => [
                'adjustment' => $adjustment,
                'obligation' => $obligation->fresh(),
            ],
        ], 201);
    }
}
