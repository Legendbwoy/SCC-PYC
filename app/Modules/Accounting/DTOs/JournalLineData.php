<?php

declare(strict_types=1);

namespace App\Modules\Accounting\DTOs;

use InvalidArgumentException;

final readonly class JournalLineData
{
    public function __construct(
        public string $accountCode,
        public int $debitPesewas = 0,
        public int $creditPesewas = 0,
        public ?string $description = null,
    ) {
        if ($debitPesewas < 0 || $creditPesewas < 0) {
            throw new InvalidArgumentException('Journal line amounts cannot be negative.');
        }

        if (($debitPesewas === 0 && $creditPesewas === 0) || ($debitPesewas > 0 && $creditPesewas > 0)) {
            throw new InvalidArgumentException('Each journal line must have either a positive debit or a positive credit, never both or zero.');
        }
    }
}
