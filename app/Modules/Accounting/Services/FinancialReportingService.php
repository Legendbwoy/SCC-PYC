<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\FinancialAccount;
use App\Modules\Reporting\Contracts\FinancialReportQueryInterface;
use App\Support\Shared\Money;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read-only ledger-derived financial statements (Spec Section 3.5, 8.1 & 14.2).
 */
class FinancialReportingService implements FinancialReportQueryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function trialBalance(DateTimeInterface $asOf): array
    {
        $asOfDate = Carbon::instance($asOf)->toDateString();

        $sums = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->whereDate('journal_entries.entry_date', '<=', $asOfDate)
            ->groupBy('journal_lines.financial_account_id')
            ->selectRaw('journal_lines.financial_account_id, SUM(journal_lines.debit_pesewas) as total_debit, SUM(journal_lines.credit_pesewas) as total_credit')
            ->get()
            ->keyBy('financial_account_id');

        $accounts = FinancialAccount::query()->where('is_postable', true)->orderBy('code')->get();

        $rows = [];
        $grandDebit = 0;
        $grandCredit = 0;

        foreach ($accounts as $account) {
            $agg = $sums->get($account->id);
            $rawDebit = (int) ($agg->total_debit ?? 0);
            $rawCredit = (int) ($agg->total_credit ?? 0);

            if ($rawDebit === 0 && $rawCredit === 0) {
                continue;
            }

            $netDebit = $rawDebit > $rawCredit ? ($rawDebit - $rawCredit) : 0;
            $netCredit = $rawCredit > $rawDebit ? ($rawCredit - $rawDebit) : 0;

            $grandDebit += $netDebit;
            $grandCredit += $netCredit;

            $rows[] = [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'debit_pesewas' => $netDebit,
                'credit_pesewas' => $netCredit,
                'debit_formatted' => Money::fromPesewas($netDebit)->format(),
                'credit_formatted' => Money::fromPesewas($netCredit)->format(),
            ];
        }

        return [
            'as_of' => $asOfDate,
            'currency' => 'GHS',
            'is_balanced' => $grandDebit === $grandCredit,
            'total_debit_pesewas' => $grandDebit,
            'total_credit_pesewas' => $grandCredit,
            'total_debit_formatted' => Money::fromPesewas($grandDebit)->format(),
            'total_credit_formatted' => Money::fromPesewas($grandCredit)->format(),
            'accounts' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function incomeAndExpenditure(string $fromDate, string $toDate): array
    {
        $sums = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->whereBetween('journal_entries.entry_date', [$fromDate, $toDate])
            ->groupBy('journal_lines.financial_account_id')
            ->selectRaw('journal_lines.financial_account_id, SUM(journal_lines.debit_pesewas) as total_debit, SUM(journal_lines.credit_pesewas) as total_credit')
            ->get()
            ->keyBy('financial_account_id');

        $accounts = FinancialAccount::query()
            ->whereIn('type', ['income', 'expense'])
            ->where('is_postable', true)
            ->orderBy('code')
            ->get();

        $incomeRows = [];
        $expenseRows = [];
        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($accounts as $account) {
            $agg = $sums->get($account->id);
            $debit = (int) ($agg->total_debit ?? 0);
            $credit = (int) ($agg->total_credit ?? 0);

            if ($account->type === 'income') {
                $net = $credit - $debit;
                if ($net !== 0) {
                    $totalIncome += $net;
                    $incomeRows[] = [
                        'code' => $account->code,
                        'name' => $account->name,
                        'amount_pesewas' => $net,
                        'formatted_amount' => Money::fromPesewas($net)->format(),
                    ];
                }
            } else {
                $net = $debit - $credit;
                if ($net !== 0) {
                    $totalExpense += $net;
                    $expenseRows[] = [
                        'code' => $account->code,
                        'name' => $account->name,
                        'amount_pesewas' => $net,
                        'formatted_amount' => Money::fromPesewas($net)->format(),
                    ];
                }
            }
        }

        $surplus = $totalIncome - $totalExpense;

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'currency' => 'GHS',
            'income' => $incomeRows,
            'expenses' => $expenseRows,
            'total_income' => Money::fromPesewas($totalIncome)->toApiArray(),
            'total_expenses' => Money::fromPesewas($totalExpense)->toApiArray(),
            'net_surplus' => Money::fromPesewas($surplus)->toApiArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function balanceSheet(DateTimeInterface $asOf): array
    {
        $asOfDate = Carbon::instance($asOf)->toDateString();

        $sums = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->whereDate('journal_entries.entry_date', '<=', $asOfDate)
            ->groupBy('journal_lines.financial_account_id')
            ->selectRaw('journal_lines.financial_account_id, SUM(journal_lines.debit_pesewas) as total_debit, SUM(journal_lines.credit_pesewas) as total_credit')
            ->get()
            ->keyBy('financial_account_id');

        $accounts = FinancialAccount::query()->where('is_postable', true)->orderBy('code')->get();

        $assets = [];
        $liabilities = [];
        $equity = [];
        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;
        $accumulatedSurplus = 0;

        foreach ($accounts as $account) {
            $agg = $sums->get($account->id);
            $debit = (int) ($agg->total_debit ?? 0);
            $credit = (int) ($agg->total_credit ?? 0);

            if ($debit === 0 && $credit === 0) {
                continue;
            }

            if ($account->type === 'asset') {
                $bal = $debit - $credit;
                $totalAssets += $bal;
                $assets[] = ['code' => $account->code, 'name' => $account->name, 'amount_pesewas' => $bal, 'formatted_amount' => Money::fromPesewas($bal)->format()];
            } elseif ($account->type === 'liability') {
                $bal = $credit - $debit;
                $totalLiabilities += $bal;
                $liabilities[] = ['code' => $account->code, 'name' => $account->name, 'amount_pesewas' => $bal, 'formatted_amount' => Money::fromPesewas($bal)->format()];
            } elseif ($account->type === 'equity') {
                $bal = $credit - $debit;
                $totalEquity += $bal;
                $equity[] = ['code' => $account->code, 'name' => $account->name, 'amount_pesewas' => $bal, 'formatted_amount' => Money::fromPesewas($bal)->format()];
            } elseif ($account->type === 'income') {
                $accumulatedSurplus += ($credit - $debit);
            } elseif ($account->type === 'expense') {
                $accumulatedSurplus -= ($debit - $credit);
            }
        }

        $totalFundsAndLiabilities = $totalLiabilities + $totalEquity + $accumulatedSurplus;

        return [
            'as_of' => $asOfDate,
            'currency' => 'GHS',
            'is_balanced' => $totalAssets === $totalFundsAndLiabilities,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'accumulated_surplus' => Money::fromPesewas($accumulatedSurplus)->toApiArray(),
            'total_assets' => Money::fromPesewas($totalAssets)->toApiArray(),
            'total_liabilities_and_funds' => Money::fromPesewas($totalFundsAndLiabilities)->toApiArray(),
        ];
    }
}
