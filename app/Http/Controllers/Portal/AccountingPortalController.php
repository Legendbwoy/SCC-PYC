<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Accounting\Contracts\AccountingPostingServiceInterface;
use App\Modules\Accounting\Models\FinancialAccount;
use App\Modules\Accounting\Models\FinancialPeriod;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Services\FinancialReportingService;
use App\Modules\Accounting\Services\PeriodClosingService;
use App\Support\Security\PycPermission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountingPortalController extends Controller
{
    public function __construct(
        private readonly AccountingPostingServiceInterface $postingService,
        private readonly PeriodClosingService $periodService,
        private readonly FinancialReportingService $reportingService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(PycPermission::AccountingView->value), 403);

        $year = now()->year;

        return view('portal.accounting.index', [
            'accounts' => FinancialAccount::query()->orderBy('code')->get(),
            'periods' => FinancialPeriod::query()->where('year', $year)->orderBy('period_key')->get(),
            'journals' => JournalEntry::query()
                ->with(['lines.account', 'poster', 'reversingEntry'])
                ->latest('id')
                ->paginate(15),
            'trialBalance' => $this->reportingService->trialBalance(now()),
            'incomeStatement' => $this->reportingService->incomeAndExpenditure("{$year}-01-01", "{$year}-12-31"),
        ]);
    }

    public function reverseJournal(Request $request, JournalEntry $journal): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $reversing = $this->postingService->reverse((int) $journal->id, $validated['reason'], $actor);

        return redirect()
            ->route('accounting.index')
            ->with('status', "Posted reversing journal {$reversing->journal_number} for {$journal->journal_number}.");
    }

    public function closePeriod(Request $request, FinancialPeriod $period): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $this->periodService->closePeriod($period, $actor, $request->string('notes')->toString() ?: null);

        return redirect()
            ->route('accounting.index')
            ->with('status', "Closed financial period {$period->period_key}.");
    }
}
