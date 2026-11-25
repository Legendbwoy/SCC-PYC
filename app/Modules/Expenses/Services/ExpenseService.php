<?php

declare(strict_types=1);

namespace App\Modules\Expenses\Services;

use App\Models\User;
use App\Modules\Accounting\Contracts\AccountingPostingServiceInterface;
use App\Modules\Accounting\DTOs\JournalLineData;
use App\Modules\Accounting\Models\FinancialApproval;
use App\Modules\Expenses\Contracts\ExpenseApprovalServiceInterface;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Expenses\Models\ExpenseCategory;
use App\Support\Audit\AuditLogger;
use App\Support\Security\MakerCheckerGuard;
use App\Support\Security\PycPermission;
use App\Support\Shared\ReferenceGenerator;
use App\Support\Shared\ReferenceType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExpenseService implements ExpenseApprovalServiceInterface
{
    public function __construct(
        private readonly MakerCheckerGuard $makerCheckerGuard,
        private readonly AccountingPostingServiceInterface $postingService,
        private readonly ReferenceGenerator $referenceGenerator,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(array $data, User $actor, bool $submitImmediately = true): Expense
    {
        if (! $actor->can(PycPermission::ExpensesCreate->value)) {
            throw new AuthorizationException('You are not authorized to record expenses.');
        }

        $amountPesewas = (int) $data['amount_pesewas'];
        if ($amountPesewas <= 0) {
            throw new InvalidArgumentException('Expense amount in pesewas must be greater than zero.');
        }

        $category = ExpenseCategory::query()->findOrFail((int) $data['expense_category_id']);
        $expenseDate = Carbon::parse((string) ($data['expense_date'] ?? now()->toDateString()));

        return DB::transaction(function () use ($category, $data, $amountPesewas, $expenseDate, $actor, $submitImmediately): Expense {
            $reference = $this->referenceGenerator->next(ReferenceType::Expense, (int) $expenseDate->year);

            $status = ($submitImmediately && $actor->can(PycPermission::ExpensesSubmit->value))
                ? 'submitted'
                : 'draft';

            $expense = Expense::query()->create([
                'reference' => $reference,
                'expense_category_id' => $category->id,
                'payee_name' => trim((string) $data['payee_name']),
                'payee_contact' => $data['payee_contact'] ?? null,
                'description' => trim((string) $data['description']),
                'amount_pesewas' => $amountPesewas,
                'currency' => 'GHS',
                'expense_date' => $expenseDate->toDateString(),
                'payment_channel' => in_array(($data['payment_channel'] ?? 'cash'), ['cash', 'bank'], true)
                    ? (string) $data['payment_channel']
                    : 'cash',
                'status' => $status,
                'created_by' => $actor->id,
                'submitted_by' => $status === 'submitted' ? $actor->id : null,
                'submitted_at' => $status === 'submitted' ? now() : null,
            ]);

            $this->auditLogger->log(
                event: 'expenses.created',
                category: 'finance',
                description: "Created expense [{$expense->reference}] ({$expense->amountMoney()->format()}) — status: {$status}.",
                auditable: $expense,
                actor: $actor,
            );

            return $expense;
        });
    }

    public function submit(Expense $expense, User $actor): Expense
    {
        if (! $actor->can(PycPermission::ExpensesSubmit->value)) {
            throw new AuthorizationException('You are not authorized to submit expenses for approval.');
        }

        return DB::transaction(function () use ($expense, $actor): Expense {
            $locked = Expense::query()->lockForUpdate()->findOrFail($expense->id);
            if ($locked->status !== 'draft') {
                throw new InvalidArgumentException("Only draft expenses can be submitted (current status: {$locked->status}).");
            }

            $locked->update([
                'status' => 'submitted',
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
            ]);

            $this->auditLogger->log(
                event: 'expenses.submitted',
                category: 'finance',
                description: "Submitted expense [{$locked->reference}] for independent approval.",
                auditable: $locked,
                actor: $actor,
            );

            return $locked;
        });
    }

    public function approve(int $expenseId, User $approver, ?string $notes = null): void
    {
        DB::transaction(function () use ($expenseId, $approver, $notes): void {
            $locked = Expense::query()->lockForUpdate()->findOrFail($expenseId);

            if ($locked->status !== 'submitted') {
                throw new InvalidArgumentException("Only submitted expenses awaiting review can be approved (current: {$locked->status}).");
            }

            $initiatorId = (int) ($locked->submitted_by ?? $locked->created_by);

            $this->makerCheckerGuard->assertCanApprove(
                approver: $approver,
                initiatorUserId: $initiatorId,
                requiredPermission: PycPermission::ExpensesApprove,
                contextLabel: "expense [{$locked->reference}]",
                auditable: $locked,
            );

            $locked->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            FinancialApproval::query()->create([
                'approvable_type' => $locked->getMorphClass(),
                'approvable_id' => $locked->id,
                'action_type' => 'expense',
                'decision' => 'approved',
                'reason' => $notes ?: 'Approved under dual authorization',
                'initiator_id' => $initiatorId,
                'approver_id' => $approver->id,
            ]);

            $this->auditLogger->log(
                event: 'expenses.approved',
                category: 'finance',
                description: "Approved expense [{$locked->reference}] ({$locked->amountMoney()->format()}).",
                auditable: $locked,
                actor: $approver,
            );
        });
    }

    public function reject(int $expenseId, User $approver, string $reason): void
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        DB::transaction(function () use ($expenseId, $approver, $reason): void {
            $locked = Expense::query()->lockForUpdate()->findOrFail($expenseId);

            if ($locked->status !== 'submitted') {
                throw new InvalidArgumentException('Only submitted expenses can be rejected.');
            }

            $initiatorId = (int) ($locked->submitted_by ?? $locked->created_by);

            $this->makerCheckerGuard->assertCanApprove(
                approver: $approver,
                initiatorUserId: $initiatorId,
                requiredPermission: PycPermission::ExpensesReject,
                contextLabel: "expense [{$locked->reference}]",
                auditable: $locked,
            );

            $locked->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

            FinancialApproval::query()->create([
                'approvable_type' => $locked->getMorphClass(),
                'approvable_id' => $locked->id,
                'action_type' => 'expense',
                'decision' => 'rejected',
                'reason' => $reason,
                'initiator_id' => $initiatorId,
                'approver_id' => $approver->id,
            ]);

            $this->auditLogger->log(
                event: 'expenses.rejected',
                category: 'finance',
                description: "Rejected expense [{$locked->reference}]: {$reason}",
                auditable: $locked,
                actor: $approver,
            );
        });
    }

    /**
     * Disburses an approved expense and posts balanced double-entry journal lines (Spec Section 8.3 & 13.5).
     */
    public function pay(Expense $expense, User $actor): Expense
    {
        if (! $actor->can(PycPermission::AccountingPost->value)) {
            throw new AuthorizationException('You are not authorized to disburse and post expenses.');
        }

        return DB::transaction(function () use ($expense, $actor): Expense {
            $locked = Expense::query()->with('category.account')->lockForUpdate()->findOrFail($expense->id);

            if ($locked->status === 'paid' && $locked->journal_entry_id !== null) {
                return $locked;
            }

            if ($locked->status !== 'approved') {
                throw new InvalidArgumentException('Expense must be independently approved before payment and posting.');
            }

            $expenseAccountCode = $locked->category?->account?->code;
            if ($expenseAccountCode === null) {
                throw new InvalidArgumentException('Expense category has no linked chart-of-accounts code.');
            }

            // Cash in Hand (1010) vs PYC Bank Account (1020)
            $assetCreditAccountCode = $locked->payment_channel === 'bank' ? '1020' : '1010';

            $journal = $this->postingService->post(
                sourceType: 'expense',
                sourceId: (int) $locked->id,
                entryDate: $locked->expense_date,
                narration: "Expense {$locked->reference} ({$locked->payee_name}): {$locked->description}",
                lines: [
                    new JournalLineData(accountCode: $expenseAccountCode, debitPesewas: $locked->amount_pesewas, description: $locked->description),
                    new JournalLineData(accountCode: $assetCreditAccountCode, creditPesewas: $locked->amount_pesewas, description: "Disbursement to {$locked->payee_name}"),
                ],
                actor: $actor,
                idempotencyKey: "expense:pay:{$locked->id}",
            );

            $locked->update([
                'status' => 'paid',
                'paid_by' => $actor->id,
                'paid_at' => now(),
                'journal_entry_id' => $journal->id,
            ]);

            $this->auditLogger->log(
                event: 'expenses.paid_and_posted',
                category: 'finance',
                description: "Disbursed and posted expense [{$locked->reference}] via journal [{$journal->journal_number}].",
                auditable: $locked,
                actor: $actor,
            );

            return $locked->fresh(['journalEntry']) ?? $locked;
        });
    }
}
