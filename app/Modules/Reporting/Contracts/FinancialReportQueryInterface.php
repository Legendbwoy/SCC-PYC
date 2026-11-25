<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Contracts;

use DateTimeInterface;

/**
 * Read-only reporting contract derived exclusively from authoritative records (Spec Section 3.5 & 14).
 */
interface FinancialReportQueryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function trialBalance(DateTimeInterface $asOf): array;
}
