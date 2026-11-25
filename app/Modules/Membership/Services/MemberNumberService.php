<?php

declare(strict_types=1);

namespace App\Modules\Membership\Services;

use App\Support\Shared\ReferenceGenerator;
use App\Support\Shared\ReferenceType;

/**
 * Generates sequential PYC-YYYY-NNNN member numbers transactionally (Spec Section 5.4 & 7.6).
 */
class MemberNumberService
{
    public function __construct(
        private readonly ReferenceGenerator $referenceGenerator,
    ) {}

    public function generate(?int $year = null): string
    {
        return $this->referenceGenerator->next(ReferenceType::Member, $year);
    }
}
