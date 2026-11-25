<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Accounting\Contracts\AccountingPostingServiceInterface;
use App\Modules\Accounting\DTOs\JournalLineData;
use App\Modules\Accounting\Models\FinancialAccount;
use App\Modules\Accounting\Models\FinancialPeriod;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Services\FinancialReportingService;
use App\Modules\Accounting\Services\PeriodClosingService;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Expenses\Services\ExpenseService;
use App\Support\Security\PycPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Accounting & Expense REST API v1 endpoints (Spec Section 13.5).
 */
class AccountingApiController extends Controller
{
    public function __construct(
        private readonly AccountingPostingServiceInterface $postingService,
        private readonly PeriodClosingService $periodService,
        private readonly FinancialReportingService $reportingService,
        private readonly ExpenseService $expenseService,
    ) {}

    public function accounts(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::AccountingView->value), 403);

        return response()->json([
            'success' => true,
            'message' => 'Chart of accounts retrieved.',
            'data' => FinancialAccount::query()->orderBy('code')->get(),
        ]);
    }

    public function journals(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::AccountingView->value), 403);

        return response()->json([
            'success' => true,
            'message' => 'Journal entries retrieved.',
            'data' => JournalEntry::query()
                ->with(['lines.account', 'period', 'poster', 'reversingEntry'])
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function showJournal(Request $request, JournalEntry $journal): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::AccountingView->value), 403);

        return response()->json([
            'success' => true,
            'message' => 'Journal entry retrieved.',
            'data' => $journal->load(['lines.account', 'period', 'poster', 'reversedJournal', 'reversingEntry']),
        ]);
    }

    public function postManualJournal(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::AccountingPost->value), 403);

        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'narration' => ['required', 'string', 'max:500'],
            'idempotency_key' => ['required', 'string', 'max:160'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_code' => ['required', 'string', 'exists:financial_accounts,code'],
            'lines.*.debit_pesewas' => ['nullable', 'integer', 'min:0'],
            'lines.*.credit_pesewas' => ['nullable', 'integer', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        $lineDtos = array_map(
            static fn (array $row): JournalLineData => new JournalLineData(
                accountCode: (string) $row['account_code'],
                debitPesewas: (int) ($row['debit_pesewas'] ?? 0),
                creditPesewas: (int) ($row['credit_pesewas'] ?? 0),
                description: $row['description'] ?? null,
            ),
            $validated['lines']
        );

        $journal = $this->postingService->post(
            sourceType: 'manual_adjustment',
            sourceId: (int) $actor->id,
            entryDate: Carbon::parse($validated['entry_date']),
            narration: $validated['narration'],
            lines: $lineDtos,
            actor: $actor,
            idempotencyKey: $validated['idempotency_key'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Balanced journal posted.',
            'data' => $journal,
        ], 201);
    }

    public function reverseJournal(Request $request, JournalEntry $journal): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $reversing = $this->postingService->reverse((int) $journal->id, $validated['reason'], $actor);

        return response()->json([
            'success' => true,
            'message' => 'Reversing journal posted.',
            'data' => $reversing,
        ], 201);
    }

    public function trialBalance(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->can(PycPermission::AccountingView->value) || $request->user()?->can(PycPermission::ReportsView->value),
            403
        );

        $asOf = $request->filled('as_of') ? Carbon::parse($request->string('as_of')->toString()) : now();

        return response()->json([
            'success' => true,
            'message' => 'Trial balance computed from posted journal lines.',
            'data' => $this->reportingService->trialBalance($asOf),
        ]);
    }

    public function incomeExpenditure(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->can(PycPermission::AccountingView->value) || $request->user()?->can(PycPermission::ReportsView->value),
            403
        );

        $year = now()->year;
        $from = $request->string('from', "{$year}-01-01")->toString();
        $to = $request->string('to', "{$year}-12-31")->toString();

        return response()->json([
            'success' => true,
            'message' => 'Income and expenditure statement computed.',
            'data' => $this->reportingService->incomeAndExpenditure($from, $to),
        ]);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->can(PycPermission::AccountingView->value) || $request->user()?->can(PycPermission::ReportsView->value),
            403
        );

        $asOf = $request->filled('as_of') ? Carbon::parse($request->string('as_of')->toString()) : now();

        return response()->json([
            'success' => true,
            'message' => 'Statement of financial position computed.',
            'data' => $this->reportingService->balanceSheet($asOf),
        ]);
    }

    public function closePeriod(Request $request, FinancialPeriod $period): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $notes = $request->string('notes')->trim()->toString() ?: null;

        $closed = $this->periodService->closePeriod($period, $actor, $notes);

        return response()->json([
            'success' => true,
            'message' => "Financial period {$closed->period_key} closed.",
            'data' => $closed,
        ]);
    }

    public function expenses(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::ExpensesView->value), 403);

        return response()->json([
            'success' => true,
            'message' => 'Expenses retrieved.',
            'data' => Expense::query()->with(['category', 'submitter', 'approver', 'journalEntry'])->latest('id')->paginate(20),
        ]);
    }

    public function storeExpense(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'payee_name' => ['required', 'string', 'max:160'],
            'payee_contact' => ['nullable', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:1000'],
            'amount_pesewas' => ['required', 'integer', 'min:1'],
            'expense_date' => ['required', 'date'],
            'payment_channel' => ['required', 'string', Rule::in(['cash', 'bank'])],
            'submit_immediately' => ['boolean'],
        ]);

        $expense = $this->expenseService->createDraft(
            data: $validated,
            actor: $actor,
            submitImmediately: (bool) ($validated['submit_immediately'] ?? false),
        );

        return response()->json([
            'success' => true,
            'message' => 'Expense created.',
            'data' => $expense,
        ], 201);
    }

    public function submitExpense(Request $request, Expense $expense): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Expense submitted for approval.',
            'data' => $this->expenseService->submit($expense, $actor),
        ]);
    }

    public function approveExpense(Request $request, Expense $expense): JsonResponse
    {
        /** @var User $approver */
        $approver = $request->user();
        $notes = $request->string('reason')->trim()->toString() ?: null;

        $this->expenseService->approve((int) $expense->id, $approver, $notes);

        return response()->json([
            'success' => true,
            'message' => 'Expense approved.',
            'data' => $expense->fresh(),
        ]);
    }

    public function rejectExpense(Request $request, Expense $expense): JsonResponse
    {
        /** @var User $approver */
        $approver = $request->user();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->expenseService->reject((int) $expense->id, $approver, $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => 'Expense rejected.',
            'data' => $expense->fresh(),
        ]);
    }

    public function payExpense(Request $request, Expense $expense): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $paid = $this->expenseService->pay($expense, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Expense disbursed and posted to ledger.',
            'data' => $paid,
        ]);
    }
}
