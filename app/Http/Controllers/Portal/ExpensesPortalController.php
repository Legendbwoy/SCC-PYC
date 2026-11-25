<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Expenses\Models\ExpenseCategory;
use App\Modules\Expenses\Services\ExpenseService;
use App\Support\Security\PycPermission;
use App\Support\Shared\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpensesPortalController extends Controller
{
    public function __construct(
        private readonly ExpenseService $expenseService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(PycPermission::ExpensesView->value), 403);

        return view('portal.expenses.index', [
            'categories' => ExpenseCategory::query()->with('account')->where('is_active', true)->orderBy('code')->get(),
            'expenses' => Expense::query()
                ->with(['category.account', 'submitter', 'approver', 'journalEntry'])
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'payee_name' => ['required', 'string', 'max:160'],
            'payee_contact' => ['nullable', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:1000'],
            'amount_ghs' => ['required', 'string', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'expense_date' => ['required', 'date'],
            'payment_channel' => ['required', 'string', Rule::in(['cash', 'bank'])],
        ]);

        $money = Money::fromMajorString($validated['amount_ghs'], 'GHS');

        $expense = $this->expenseService->createDraft(
            data: array_merge($validated, ['amount_pesewas' => $money->pesewas]),
            actor: $actor,
            submitImmediately: true,
        );

        return redirect()
            ->route('expenses.index')
            ->with('status', "Submitted expense {$expense->reference} ({$money->format()}) for independent approval.");
    }

    public function approve(Request $request, Expense $expense): RedirectResponse
    {
        /** @var User $approver */
        $approver = $request->user();

        $this->expenseService->approve((int) $expense->id, $approver, $request->string('reason')->toString() ?: null);

        return redirect()
            ->route('expenses.index')
            ->with('status', "Approved expense {$expense->reference}.");
    }

    public function reject(Request $request, Expense $expense): RedirectResponse
    {
        /** @var User $approver */
        $approver = $request->user();

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->expenseService->reject((int) $expense->id, $approver, $validated['reason']);

        return redirect()
            ->route('expenses.index')
            ->with('status', "Rejected expense {$expense->reference}.");
    }

    public function pay(Request $request, Expense $expense): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $paid = $this->expenseService->pay($expense, $actor);

        return redirect()
            ->route('expenses.index')
            ->with('status', "Disbursed and posted expense {$paid->reference} to ledger ({$paid->journalEntry?->journal_number}).");
    }
}
