<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Contracts;

use App\Models\User;
use App\Modules\Accounting\DTOs\JournalLineData;
use App\Modules\Accounting\Models\JournalEntry;
use DateTimeInterface;

/**
 * Sole authority for double-entry journal creation, balance validation, posting, and reversal.
 * Spec Section 3.5 & 8.1: No other module may create or modify journal records directly.
 */
interface AccountingPostingServiceInterface
{
    /**
     * Validates that at least 2 lines exist and total debits strictly equal total credits in pesewas.
     *
     * @param  list<JournalLineData>  $lines
     */
    public function validateBalance(array $lines): void;

    /**
     * Atomically creates and posts a balanced journal entry in an open financial period.
     *
     * @param  list<JournalLineData>  $lines
     */
    public function post(
        string $sourceType,
        int $sourceId,
        DateTimeInterface $entryDate,
        string $narration,
        array $lines,
        ?User $actor = null,
        ?string $idempotencyKey = null,
    ): JournalEntry;

    /**
     * Creates an immutable reversing journal entry for a previously posted journal.
     */
    public function reverse(
        int $journalEntryId,
        string $reason,
        User $actor,
    ): JournalEntry;
}
