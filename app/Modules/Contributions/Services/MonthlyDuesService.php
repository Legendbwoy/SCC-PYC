<?php

declare(strict_types=1);

namespace App\Modules\Contributions\Services;

use App\Models\User;
use App\Modules\Contributions\Enums\ObligationStatus;
use App\Modules\Contributions\Models\MemberObligation;
use App\Modules\Membership\Enums\MemberStatus;
use App\Modules\Membership\Models\Member;
use App\Support\Audit\AuditLogger;
use App\Support\Shared\Money;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Idempotent monthly dues obligation generator & operational balance calculator (Spec Section 6.1 & 12.2).
 */
class MonthlyDuesService
{
    public function __construct(
        private readonly ContributionCampaignService $campaignService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Generates monthly dues obligations (e.g. 1,000 pesewas = GHS 10.00) idempotently for period YYYY-MM.
     *
     * @return array{period: string, campaign_id: int, amount_pesewas: int, generated_count: int, skipped_count: int}
     */
    public function generate(string $period, ?User $initiatedBy = null): array
    {
        [$periodStart, $periodEnd] = $this->parseMonthlyPeriod($period);
        $campaign = $this->campaignService->resolveEffectiveMonthlyDuesCampaign($periodStart);
        $dueDate = $periodStart->copy()->day(min($campaign->due_day_of_month, $periodEnd->day));

        $eligibleMembers = Member::query()
            ->where('status', MemberStatus::Active->value)
            ->where(static function ($q) use ($periodEnd): void {
                $q->whereNull('joined_at')
                    ->orWhereDate('joined_at', '<=', $periodEnd->toDateString());
            })
            ->get();

        $generated = 0;
        $skipped = 0;

        foreach ($eligibleMembers as $member) {
            $exists = MemberObligation::query()
                ->where('member_id', $member->id)
                ->where('contribution_campaign_id', $campaign->id)
                ->where('period_key', $period)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            try {
                MemberObligation::query()->create([
                    'member_id' => $member->id,
                    'contribution_campaign_id' => $campaign->id,
                    'period_key' => $period,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'due_date' => $dueDate->toDateString(),
                    'amount_due_pesewas' => $campaign->amount_pesewas,
                    'amount_allocated_pesewas' => 0,
                    'adjustment_pesewas' => 0,
                    'currency' => $campaign->currency,
                    'status' => ObligationStatus::Unpaid->value,
                    'generated_by' => $initiatedBy?->id,
                ]);
                $generated++;
            } catch (QueryException) {
                $skipped++;
            }
        }

        $this->auditLogger->log(
            event: 'dues.monthly_generated',
            category: 'finance',
            description: "Generated monthly dues for [{$period}]: {$generated} created, {$skipped} existing skipped.",
            metadata: [
                'period' => $period,
                'campaign_id' => $campaign->id,
                'amount_pesewas' => $campaign->amount_pesewas,
                'generated_count' => $generated,
                'skipped_count' => $skipped,
            ],
            actor: $initiatedBy,
        );

        return [
            'period' => $period,
            'campaign_id' => $campaign->id,
            'amount_pesewas' => $campaign->amount_pesewas,
            'generated_count' => $generated,
            'skipped_count' => $skipped,
        ];
    }

    /**
     * Ensures a monthly dues obligation exists for a specific member and YYYY-MM period (used for advance payments).
     */
    public function ensureMemberObligationForPeriod(Member $member, string $period, ?User $actor = null): MemberObligation
    {
        [$periodStart, $periodEnd] = $this->parseMonthlyPeriod($period);
        $campaign = $this->campaignService->resolveEffectiveMonthlyDuesCampaign($periodStart);

        if (! $campaign->allow_advance_payments && $periodStart->isFuture()) {
            throw new InvalidArgumentException("Advance payments are disabled for campaign [{$campaign->title}].");
        }

        $dueDate = $periodStart->copy()->day(min($campaign->due_day_of_month, $periodEnd->day));

        return MemberObligation::query()->firstOrCreate(
            [
                'member_id' => $member->id,
                'contribution_campaign_id' => $campaign->id,
                'period_key' => $period,
            ],
            [
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'amount_due_pesewas' => $campaign->amount_pesewas,
                'amount_allocated_pesewas' => 0,
                'adjustment_pesewas' => 0,
                'currency' => $campaign->currency,
                'status' => ObligationStatus::Unpaid->value,
                'generated_by' => $actor?->id,
            ]
        );
    }

    /**
     * Calculates total operational outstanding balance for a member without inflating ledger income (Spec 6.4).
     */
    public function calculateOutstanding(Member $member): Money
    {
        $obligations = MemberObligation::query()
            ->where('member_id', $member->id)
            ->whereIn('status', [ObligationStatus::Unpaid->value, ObligationStatus::PartiallyPaid->value])
            ->get();

        $totalPesewas = 0;
        foreach ($obligations as $obligation) {
            $totalPesewas += $obligation->outstandingPesewas();
        }

        return Money::fromPesewas($totalPesewas, (string) config('pyc.organization.currency', 'GHS'));
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function parseMonthlyPeriod(string $period): array
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', trim($period))) {
            throw new InvalidArgumentException("Invalid monthly period [{$period}]; expected YYYY-MM.");
        }

        $start = Carbon::createFromFormat('Y-m-d', trim($period).'-01', (string) config('pyc.organization.timezone', 'Africa/Accra'))
            ->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [$start, $end];
    }
}
