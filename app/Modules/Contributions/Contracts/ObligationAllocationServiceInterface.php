<?php

declare(strict_types=1);

namespace App\Modules\Contributions\Contracts;

/**
 * Shared obligation allocation contract used by monthly dues, event contributions, and special collections (Spec Section 6.2).
 */
interface ObligationAllocationServiceInterface
{
    /**
     * @param  list<array{obligation_id: int, amount_pesewas: int}>  $explicitAllocations
     */
    public function allocatePayment(int $paymentId, int $memberId, int $amountPesewas, array $explicitAllocations = []): void;
}
