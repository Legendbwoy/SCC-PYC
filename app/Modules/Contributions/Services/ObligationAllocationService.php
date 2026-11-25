<?php

declare(strict_types=1);

namespace App\Modules\Contributions\Services;

use App\Modules\Contributions\Contracts\ObligationAllocationServiceInterface;
use App\Modules\Contributions\Enums\ObligationStatus;
use App\Modules\Contributions\Models\MemberObligation;
use App\Modules\Membership\Models\Member;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Allocates payments across one or multiple member obligations, supporting both
 * partial payments and automatic future-period advance allocations (Spec Section 6.1, 6.3 & 17.2).
 */
class ObligationAllocationService implements ObligationAllocationServiceInterface
{
    public function __construct(
        private readonly MonthlyDuesService $monthlyDuesService,
    ) {}

    /**
     * @param  list<array{obligation_id: int, amount_pesewas: int}>  $explicitAllocations
     * @return list<array{obligation_id: int, campaign_id: int, period_key: string, amount_pesewas: int, income_account_code: string}>
     */
    public function allocate(
        int $memberId,
        int $amountPesewas,
        array $explicitAllocations = [],
        bool $allowAdvanceGeneration = true,
    ): array {
        if ($amountPesewas <= 0) {
            throw new InvalidArgumentException('Allocation amount in pesewas must be greater than zero.');
        }

        return DB::transaction(function () use ($memberId, $amountPesewas, $explicitAllocations, $allowAdvanceGeneration): array {
            $member = Member::query()->lockForUpdate()->findOrFail($memberId);

            if ($explicitAllocations !== []) {
                return $this->applyExplicitAllocations($member, $amountPesewas, $explicitAllocations);
            }

            return $this->applyFifoAndAdvanceAllocations($member, $amountPesewas, $allowAdvanceGeneration);
        });
    }

    /**
     * @param  list<array{obligation_id: int, amount_pesewas: int}>  $explicitAllocations
     * @return list<array{obligation_id: int, campaign_id: int, period_key: string, amount_pesewas: int, income_account_code: string}>
     */
    private function applyExplicitAllocations(Member $member, int $totalPesewas, array $explicitAllocations): array
    {
        $sumExplicit = array_sum(array_map(static fn (array $item): int => (int) $item['amount_pesewas'], $explicitAllocations));
        if ($sumExplicit !== $totalPesewas) {
            throw new InvalidArgumentException(
                "Explicit allocation sum ({$sumExplicit}p) does not match payment amount ({$totalPesewas}p)."
            );
        }

        $results = [];

        foreach ($explicitAllocations as $slice) {
            $sliceAmount = (int) $slice['amount_pesewas'];
            if ($sliceAmount <= 0) {
                throw new InvalidArgumentException('Each allocation slice must be positive.');
            }

            $obligation = MemberObligation::query()
                ->with('campaign.type')
                ->where('member_id', $member->id)
                ->lockForUpdate()
                ->findOrFail((int) $slice['obligation_id']);

            if (! $obligation->campaign?->allow_partial_payments && $sliceAmount < $obligation->outstandingPesewas()) {
                throw new InvalidArgumentException("Partial payments are not permitted for [{$obligation->campaign?->title}].");
            }

            if ($sliceAmount > $obligation->outstandingPesewas()) {
                throw new InvalidArgumentException(
                    "Allocation ({$sliceAmount}p) exceeds remaining balance ({$obligation->outstandingPesewas()}p) on obligation #{$obligation->id}."
                );
            }

            $this->applySliceToObligation($obligation, $sliceAmount);

            $results[] = [
                'obligation_id' => (int) $obligation->id,
                'campaign_id' => (int) $obligation->contribution_campaign_id,
                'period_key' => $obligation->period_key,
                'amount_pesewas' => $sliceAmount,
                'income_account_code' => (string) ($obligation->campaign?->type?->default_income_account_code ?? '4000'),
            ];
        }

        return $results;
    }

    /**
     * @return list<array{obligation_id: int, campaign_id: int, period_key: string, amount_pesewas: int, income_account_code: string}>
     */
    private function applyFifoAndAdvanceAllocations(Member $member, int $amountPesewas, bool $allowAdvanceGeneration): array
    {
        $remaining = $amountPesewas;
        $results = [];

        $openObligations = MemberObligation::query()
            ->with('campaign.type')
            ->where('member_id', $member->id)
            ->whereIn('status', [ObligationStatus::Unpaid->value, ObligationStatus::PartiallyPaid->value])
            ->orderBy('due_date')
            ->orderBy('period_key')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($openObligations as $obligation) {
            if ($remaining <= 0) {
                break;
            }

            $outstanding = $obligation->outstandingPesewas();
            if ($outstanding <= 0) {
                continue;
            }

            $slice = min($remaining, $outstanding);

            if (! ($obligation->campaign?->allow_partial_payments ?? true) && $slice < $outstanding) {
                continue;
            }

            $this->applySliceToObligation($obligation, $slice);
            $remaining -= $slice;

            $results[] = [
                'obligation_id' => (int) $obligation->id,
                'campaign_id' => (int) $obligation->contribution_campaign_id,
                'period_key' => $obligation->period_key,
                'amount_pesewas' => $slice,
                'income_account_code' => (string) ($obligation->campaign?->type?->default_income_account_code ?? '4000'),
            ];
        }

        // Advance monthly dues allocation when payment exceeds currently generated obligations (Spec 6.1 & 17.2)
        if ($remaining > 0 && $allowAdvanceGeneration) {
            $cursor = $this->resolveNextAdvanceMonthStart($member);
            $safetyMonths = 0;

            while ($remaining > 0 && $safetyMonths < 60) {
                $periodKey = $cursor->format('Y-m');
                $futureObligation = $this->monthlyDuesService->ensureMemberObligationForPeriod($member, $periodKey);
                $lockedFuture = MemberObligation::query()
                    ->with('campaign.type')
                    ->lockForUpdate()
                    ->findOrFail($futureObligation->id);

                $outstanding = $lockedFuture->outstandingPesewas();
                if ($outstanding > 0) {
                    $slice = min($remaining, $outstanding);
                    $this->applySliceToObligation($lockedFuture, $slice);
                    $remaining -= $slice;

                    $results[] = [
                        'obligation_id' => (int) $lockedFuture->id,
                        'campaign_id' => (int) $lockedFuture->contribution_campaign_id,
                        'period_key' => $lockedFuture->period_key,
                        'amount_pesewas' => $slice,
                        'income_account_code' => (string) ($lockedFuture->campaign?->type?->default_income_account_code ?? '4000'),
                    ];
                }

                $cursor = $cursor->addMonthNoOverflow();
                $safetyMonths++;
            }
        }

        if ($remaining > 0) {
            throw new InvalidArgumentException(
                "Unallocated payment remainder of {$remaining} pesewas could not be matched to any eligible obligation."
            );
        }

        return $results;
    }

    private function applySliceToObligation(MemberObligation $obligation, int $slicePesewas): void
    {
        $newAllocated = $obligation->amount_allocated_pesewas + $slicePesewas;
        $required = $obligation->effectiveRequiredPesewas();

        $newStatus = $newAllocated >= $required
            ? ObligationStatus::Paid->value
            : ObligationStatus::PartiallyPaid->value;

        $obligation->update([
            'amount_allocated_pesewas' => $newAllocated,
            'status' => $newStatus,
        ]);
    }

    private function resolveNextAdvanceMonthStart(Member $member): Carbon
    {
        $tz = (string) config('pyc.organization.timezone', 'Africa/Accra');

        $latestMonthlyPeriod = MemberObligation::query()
            ->where('member_id', $member->id)
            ->where('period_key', 'regexp', '^[0-9]{4}-[0-9]{2}$')
            ->orderByDesc('period_key')
            ->value('period_key');

        if (is_string($latestMonthlyPeriod) && $latestMonthlyPeriod !== '') {
            return Carbon::createFromFormat('Y-m-d', $latestMonthlyPeriod.'-01', $tz)
                ->startOfMonth()
                ->addMonthNoOverflow();
        }

        return Carbon::now($tz)->startOfMonth();
    }
}
