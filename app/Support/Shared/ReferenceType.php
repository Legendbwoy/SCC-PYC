<?php

declare(strict_types=1);

namespace App\Support\Shared;

/**
 * Official reference types and format rules from Specification Section 7.6.
 */
enum ReferenceType: string
{
    case Member = 'member';
    case Payment = 'payment';
    case Receipt = 'receipt';
    case Journal = 'journal';
    case Expense = 'expense';
    case Event = 'event';
    case Refund = 'refund';

    public function codeSegment(): ?string
    {
        return match ($this) {
            self::Member => null,
            self::Payment => 'PAY',
            self::Receipt => 'RCT',
            self::Journal => 'JRN',
            self::Expense => 'EXP',
            self::Event => 'EVT',
            self::Refund => 'REF',
        };
    }

    public function paddingWidth(): int
    {
        return match ($this) {
            self::Member => 4,
            default => 5,
        };
    }
}
