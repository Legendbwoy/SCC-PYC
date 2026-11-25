<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Services;

use App\Models\User;
use App\Modules\Accounting\Contracts\AccountingPostingServiceInterface;
use App\Modules\Accounting\DTOs\JournalLineData;
use App\Modules\Accounting\Models\FinancialAccount;
use App\Modules\Accounting\Models\FinancialApproval;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Models\JournalLine;
use App\Support\Audit\AuditLogger;
use App\Support\Security\PycPermission;
use App\Support\Shared\ReferenceGenerator;
use App\Support\Shared\ReferenceType;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Sole authority for double-entry journal creation, balance validation, posting, and reversal (Spec Section 8.1 & 8.6).
 */
class JournalPostingService implements AccountingPostingServiceInterface
{
    public function __construct(
        private readonly PeriodClosingService $periodService,
        private readonly ReferenceGenerator $referenceGenerator,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  list<JournalLineData>  $lines
     */
    public function validateBalance(array $lines): void
    {
        if (count($lines) < 2) {
            throw new InvalidArgumentException('Every posted journal entry must contain at least two lines.');
        }

        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($lines as $line) {
            $totalDebits += $line->debitPesewas;
            $totalCredits += $line->creditPesewas;
        }

        if ($totalDebits <= 0 || $totalDebits !== $totalCredits) {
            throw new InvalidArgumentException(
                "Unbalanced journal entry rejected: total debits ({$totalDebits}p) must equal total credits ({$totalCredits}p) and be positive."
            );
        }
    }

    /**
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
    ): JournalEntry {
        $this->validateBalance($lines);

        $resolvedKey = $idempotencyKey ?: "{$sourceType}:{$sourceId}";
        $carbonDate = Carbon::instance($entryDate);

        return DB::transaction(function () use ($sourceType, $sourceId, $carbonDate, $narration, $lines, $actor, $resolvedKey): JournalEntry {
            $existing = JournalEntry::query()
                ->with('lines.account')
                ->where('idempotency_key', $resolvedKey)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $period = $this->periodService->resolveOpenMonthlyPeriodForPosting($carbonDate);

            $codes = array_values(array_unique(array_map(static fn (JournalLineData $l): string => $l->accountCode, $lines)));
            $accountsByCode = FinancialAccount::query()
                ->whereIn('code', $codes)
                ->where('is_active', true)
                ->get()
                ->keyBy('code');

            foreach ($lines as $line) {
                /** @var FinancialAccount|null $acc */
                $acc = $accountsByCode->get($line->accountCode);
                if ($acc === null) {
                    throw new InvalidArgumentException("Financial account code [{$line->accountCode}] does not exist or is inactive.");
                }
                if (! $acc->is_postable) {
                    throw new InvalidArgumentException("Header account [{$acc->code} - {$acc->name}] cannot be posted to directly.");
                }
            }

            $journalNumber = $this->referenceGenerator->next(ReferenceType::Journal, (int) $carbonDate->year);

            $entry = JournalEntry::query()->create([
                'journal_number' => $journalNumber,
                'financial_period_id' => $period->id,
                'entry_date' => $carbonDate->toDateString(),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'idempotency_key' => $resolvedKey,
                'narration' => $narration,
                'posted_by' => $actor?->id,
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                /** @var FinancialAccount $account */
                $account = $accountsByCode->get($line->accountCode);

                JournalLine::query()->create([
                    'journal_entry_id' => $entry->id,
                    'financial_account_id' => $account->id,
                    'debit_pesewas' => $line->debitPesewas,
                    'credit_pesewas' => $line->creditPesewas,
                    'description' => $line->description ?? $narration,
                ]);
            }

            $this->auditLogger->log(
                event: 'accounting.journal_posted',
                category: 'finance',
                description: "Posted journal [{$entry->journal_number}]: {$narration}",
                auditable: $entry,
                newValues: [
                    'journal_number' => $entry->journal_number,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'period' => $period->period_key,
                ],
                actor: $actor,
            );

            return $entry->load('lines.account');
        });
    }

    public function reverse(
        int $journalEntryId,
        string $reason,
        User $actor,
    ): JournalEntry {
        if (! $actor->can(PycPermission::AccountingReverse->value)) {
            throw new AuthorizationException('You are not authorized to reverse posted journal entries.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('A clear audit reason is required to reverse a posted journal entry.');
        }

        return DB::transaction(function () use ($journalEntryId, $reason, $actor): JournalEntry {
            $original = JournalEntry::query()
                ->with(['lines.account', 'reversingEntry'])
                ->lockForUpdate()
                ->findOrFail($journalEntryId);

            if ($original->reversingEntry !== null) {
                throw new InvalidArgumentException("Journal [{$original->journal_number}] has already been reversed by [{$original->reversingEntry->journal_number}].");
            }

            if ($original->reversal_of_journal_id !== null) {
                throw new InvalidArgumentException('Reversing journal entries cannot themselves be reversed.');
            }

            $reversalDate = Carbon::now((string) config('pyc.organization.timezone', 'Africa/Accra'));
            $period = $this->periodService->resolveOpenMonthlyPeriodForPosting($reversalDate);
            $journalNumber = $this->referenceGenerator->next(ReferenceType::Journal, (int) $reversalDate->year);

            $reversingEntry = JournalEntry::query()->create([
                'journal_number' => $journalNumber,
                'financial_period_id' => $period->id,
                'entry_date' => $reversalDate->toDateString(),
                'source_type' => 'journal_reversal',
                'source_id' => $original->id,
                'idempotency_key' => "reversal:journal:{$original->id}",
                'narration' => "Reversal of {$original->journal_number}: {$reason}",
                'reversal_of_journal_id' => $original->id,
                'reversal_reason' => $reason,
                'posted_by' => $actor->id,
                'posted_at' => now(),
            ]);

            foreach ($original->lines as $origLine) {
                JournalLine::query()->create([
                    'journal_entry_id' => $reversingEntry->id,
                    'financial_account_id' => $origLine->financial_account_id,
                    'debit_pesewas' => $origLine->credit_pesewas,
                    'credit_pesewas' => $origLine->debit_pesewas,
                    'description' => "Reversal of {$original->journal_number}",
                ]);
            }

            FinancialApproval::query()->create([
                'approvable_type' => $original->getMorphClass(),
                'approvable_id' => $original->id,
                'action_type' => 'journal_reversal',
                'decision' => 'approved',
                'reason' => $reason,
                'initiator_id' => $original->posted_by ?? $actor->id,
                'approver_id' => $actor->id,
            ]);

            $this->auditLogger->log(
                event: 'accounting.journal_reversed',
                category: 'finance',
                description: "Reversed journal [{$original->journal_number}] via [{$reversingEntry->journal_number}].",
                auditable: $reversingEntry,
                metadata: [
                    'original_journal_id' => $original->id,
                    'original_journal_number' => $original->journal_number,
                    'reason' => $reason,
                ],
                actor: $actor,
            );

            return $reversingEntry->load('lines.account');
        });
    }
}
