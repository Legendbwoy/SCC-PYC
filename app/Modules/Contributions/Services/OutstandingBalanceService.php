<?php

declare(strict_types=1);

namespace App\Modules\Contributions\Services;

use App\Modules\Contributions\Enums\ObligationStatus;
use App\Modules\Contributions\Models\MemberObligation;
use App\Modules\Membership\Models\Member;
use App\Support\Shared\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read-only operational balance & arrears reporting service (Spec Section 6.4 & 12.2).
 */
class OutstandingBalanceService
{
    /**
     * Builds a complete personal contribution statement for a member.
     *
     * @return array{
     *   obligations: Collection<int, MemberObligation>,
     *   total_billed: Money,
     *   total_adjusted: Money,
     *   total_paid: Money,
     *   arrears_due: Money,
     *   total_outstanding: Money
     * }
     */
    public function memberStatement(Member $member): array
    {
        $obligations = MemberObligation::query()
            ->with(['campaign.type', 'adjustments'])
            ->where('member_id', $member->id)
            ->orderByDesc('period_key')
            ->orderByDesc('id')
            ->get();

        $today = Carbon::now((string) config('pyc.organization.timezone', 'Africa/Accra'))->toDateString();

        $billed = 0;
        $adjusted = 0;
        $paid = 0;
        $arrears = 0;
        $outstanding = 0;

        foreach ($obligations as $item) {
            $billed += $item->amount_due_pesewas;
            $adjusted += $item->adjustment_pesewas;
            $paid += $item->amount_allocated_pesewas;

            $rem = $item->outstandingPesewas();
            $outstanding += $rem;

            if ($rem > 0 && ($item->due_date === null || $item->due_date->toDateString() <= $today)) {
                $arrears += $rem;
            }
        }

        return [
            'obligations' => $obligations,
            'total_billed' => Money::fromPesewas($billed, 'GHS'),
            'total_adjusted' => Money::fromPesewas($adjusted, 'GHS'),
            'total_paid' => Money::fromPesewas($paid, 'GHS'),
            'arrears_due' => Money::fromPesewas($arrears, 'GHS'),
            'total_outstanding' => Money::fromPesewas($outstanding, 'GHS'),
        ];
    }

    /**
     * Returns members with unpaid operational balances.
     *
     * @return Collection<int, array{member: Member, obligations_count: int, outstanding_pesewas: int, outstanding_money: Money}>
     */
    public function arrearsSummary(?int $campaignId = null): Collection
    {
        $query = MemberObligation::query()
            ->with('member')
            ->whereIn('status', [ObligationStatus::Unpaid->value, ObligationStatus::PartiallyPaid->value]);

        if ($campaignId !== null) {
            $query->where('contribution_campaign_id', $campaignId);
        }

        return $query->get()
            ->groupBy('member_id')
            ->map(static function (Collection $items): ?array {
                /** @var MemberObligation|null $first */
                $first = $items->first();
                if ($first === null || $first->member === null) {
                    return null;
                }

                $sum = 0;
                foreach ($items as $ob) {
                    $sum += $ob->outstandingPesewas();
                }

                if ($sum <= 0) {
                    return null;
                }

                return [
                    'member' => $first->member,
                    'obligations_count' => $items->count(),
                    'outstanding_pesewas' => $sum,
                    'outstanding_money' => Money::fromPesewas($sum, 'GHS'),
                ];
            })
            ->filter()
            ->sortByDesc('outstanding_pesewas')
            ->values();
    }

    /**
     * @return array{expected: Money, adjusted: Money, collected: Money, outstanding: Money}
     */
    public function totals(?int $campaignId = null, ?string $period = null): array
    {
        $query = MemberObligation::query();

        if ($campaignId !== null) {
            $query->where('contribution_campaign_id', $campaignId);
        }

        if ($period !== null && $period !== '') {
            $query->where('period_key', $period);
        }

        $rows = $query->get();

        $expected = 0;
        $adjusted = 0;
        $collected = 0;
        $outstanding = 0;

        foreach ($rows as $row) {
            $expected += $row->amount_due_pesewas;
            $adjusted += $row->adjustment_pesewas;
            $collected += $row->amount_allocated_pesewas;
            $outstanding += $row->outstandingPesewas();
        }

        return [
            'expected' => Money::fromPesewas($expected, 'GHS'),
            'adjusted' => Money::fromPesewas($adjusted, 'GHS'),
            'collected' => Money::fromPesewas($collected, 'GHS'),
            'outstanding' => Money::fromPesewas($outstanding, 'GHS'),
        ];
    }
}
