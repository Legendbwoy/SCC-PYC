<?php

declare(strict_types=1);

namespace App\Modules\Contributions\Contracts;

/**
 * Shared obligation allocation contract used by monthly dues, event contributions, and special collections (Spec Section 6.2 & 6.3).
 */
interface ObligationAllocationServiceInterface
{
    /**
     * Allocates integer pesewas across explicit obligations or FIFO unpaid/future obligations for a member.
     *
     * @param  list<array{obligation_id: int, amount_pesewas: int}>  $explicitAllocations
     * @return list<array{obligation_id: int, campaign_id: int, period_key: string, amount_pesewas: int, income_account_code: string}>
     */
    public function allocate(
        int $memberId,
        int $amountPesewas,
        array $explicitAllocations = [],
        bool $allowAdvanceGeneration = true,
    ): array;
}
