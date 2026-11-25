<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Contributions\Models\ContributionCampaign;
use App\Modules\Contributions\Models\ContributionType;
use App\Modules\Contributions\Models\MemberObligation;
use App\Modules\Contributions\Services\ContributionCampaignService;
use App\Modules\Contributions\Services\MonthlyDuesService;
use App\Modules\Contributions\Services\ObligationAdjustmentService;
use App\Modules\Contributions\Services\OutstandingBalanceService;
use App\Support\Security\PycPermission;
use App\Support\Shared\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ContributionsController extends Controller
{
    public function __construct(
        private readonly ContributionCampaignService $campaignService,
        private readonly MonthlyDuesService $monthlyDuesService,
        private readonly ObligationAdjustmentService $adjustmentService,
        private readonly OutstandingBalanceService $balanceService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(PycPermission::DuesView->value), 403);

        $tz = (string) config('pyc.organization.timezone', 'Africa/Accra');

        return view('portal.contributions.index', [
            'currentPeriod' => Carbon::now($tz)->format('Y-m'),
            'totals' => $this->balanceService->totals(),
            'types' => ContributionType::query()->where('is_active', true)->orderBy('id')->get(),
            'campaigns' => ContributionCampaign::query()->with('type')->withCount('obligations')->latest('id')->get(),
            'arrearsSummary' => $this->balanceService->arrearsSummary()->take(25),
        ]);
    }

    public function obligations(Request $request): View
    {
        abort_unless($request->user()?->can(PycPermission::DuesView->value), 403);

        $status = $request->string('status')->trim()->toString();
        $period = $request->string('period')->trim()->toString();
        $campaignId = $request->integer('campaign_id');
        $search = $request->string('q')->trim()->toString();

        $query = MemberObligation::query()
            ->with(['member', 'campaign', 'adjustments'])
            ->orderByDesc('period_key')
            ->latest('id');

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($period !== '') {
            $query->where('period_key', $period);
        }

        if ($campaignId > 0) {
            $query->where('contribution_campaign_id', $campaignId);
        }

        if ($search !== '') {
            $query->whereHas('member', static function ($mq) use ($search): void {
                $mq->where('member_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        return view('portal.contributions.obligations', [
            'obligations' => $query->paginate(25)->withQueryString(),
            'campaigns' => ContributionCampaign::query()->orderBy('title')->get(),
            'filters' => [
                'status' => $status,
                'period' => $period,
                'campaign_id' => $campaignId,
                'q' => $search,
            ],
        ]);
    }

    public function generateDues(Request $request): RedirectResponse
    {
        Gate::authorize('generate', MemberObligation::class);

        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'period' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $result = $this->monthlyDuesService->generate($validated['period'], $actor);

        return redirect()
            ->route('contributions.index')
            ->with('status', "Monthly dues for {$result['period']}: {$result['generated_count']} obligations generated, {$result['skipped_count']} existing skipped.");
    }

    public function storeCampaign(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can(PycPermission::DuesGenerate->value), 403);

        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'contribution_type_id' => ['required', 'integer', 'exists:contribution_types,id'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount_ghs' => ['required', 'string', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'frequency' => ['required', 'string', Rule::in(['monthly', 'one_off', 'annual'])],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'generate_obligations_now' => ['nullable', 'boolean'],
        ]);

        $money = Money::fromMajorString($validated['amount_ghs'], 'GHS');

        $campaign = $this->campaignService->createCampaign(
            data: [
                'contribution_type_id' => $validated['contribution_type_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'amount_pesewas' => $money->pesewas,
                'frequency' => $validated['frequency'],
                'effective_from' => $validated['effective_from'],
                'effective_to' => $validated['effective_to'] ?? null,
            ],
            actor: $actor,
            generateOneOffObligations: $request->boolean('generate_obligations_now'),
        );

        return redirect()
            ->route('contributions.index')
            ->with('status', "Campaign '{$campaign->title}' ({$money->format()}) created.");
    }

    public function adjustObligation(Request $request, MemberObligation $obligation): RedirectResponse
    {
        Gate::authorize('adjust', $obligation);

        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'adjustment_type' => ['required', 'string', Rule::in(['waiver', 'exemption', 'discount', 'surcharge'])],
            'amount_ghs' => ['nullable', 'string', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $pesewas = ! empty($validated['amount_ghs'])
            ? Money::fromMajorString($validated['amount_ghs'], 'GHS')->pesewas
            : 0;

        $this->adjustmentService->applyAdjustment(
            obligation: $obligation,
            adjustmentType: $validated['adjustment_type'],
            amountPesewas: $pesewas,
            reason: $validated['reason'],
            actor: $actor,
        );

        return back()->with('status', "Adjustment applied to obligation #{$obligation->id}.");
    }
}
