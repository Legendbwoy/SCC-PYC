<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Services;

use App\Models\User;
use App\Modules\Accounting\Models\FinancialAccount;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Accounting\Services\FinancialReportingService;
use App\Modules\Contributions\Services\OutstandingBalanceService;
use App\Modules\Events\Models\PycEvent;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Membership\Models\Member;
use App\Modules\Membership\Models\MemberGroup;
use App\Modules\Membership\Models\MemberSubmission;
use App\Modules\Notifications\Models\NotificationDelivery;
use App\Support\Audit\AuditLogger;
use App\Support\Shared\Money;
use App\Support\Shared\SystemSettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Authoritative read-only reporting & PDF/Excel-CSV export service (Spec Section 3.5 & 14).
 */
class ReportingService
{
    public function __construct(
        private readonly FinancialReportingService $financialReporting,
        private readonly OutstandingBalanceService $balanceService,
        private readonly SystemSettingService $settingService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Consolidated executive & auditor report dashboard snapshot (Spec Section 14.1–14.3).
     *
     * @return array<string, mixed>
     */
    public function executiveSnapshot(string $fromDate, string $toDate): array
    {
        $asOf = Carbon::parse($toDate)->endOfDay();

        $membershipByStatus = Member::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $membershipByGroup = MemberGroup::query()
            ->withCount(['members' => static fn ($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get();

        $syncErrorsCount = MemberSubmission::query()->where('sync_status', 'validation_failed')->count();
        $duplicatesCount = Member::query()->where('is_duplicate_flagged', true)->where('status', 'pending')->count();

        $duesTotals = $this->balanceService->totals();
        $trialBalance = $this->financialReporting->trialBalance($asOf);
        $incomeExpenditure = $this->financialReporting->incomeAndExpenditure($fromDate, $toDate);
        $balanceSheet = $this->financialReporting->balanceSheet($asOf);

        $commsByChannelAndStatus = NotificationDelivery::query()
            ->selectRaw('channel, status, count(*) as total, COALESCE(SUM(cost_pesewas), 0) as cost_pesewas')
            ->groupBy('channel', 'status')
            ->get();

        $eventSummaries = PycEvent::query()
            ->with('contributionCampaign')
            ->withCount([
                'registrations as registered_count' => static fn ($q) => $q->whereIn('status', ['registered', 'attended']),
                'registrations as attended_count' => static fn ($q) => $q->where('status', 'attended'),
            ])
            ->latest('starts_at')
            ->limit(10)
            ->get();

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'membership' => [
                'by_status' => $membershipByStatus,
                'by_group' => $membershipByGroup,
                'sync_errors' => $syncErrorsCount,
                'duplicate_candidates' => $duplicatesCount,
            ],
            'finance' => [
                'dues_totals' => $duesTotals,
                'trial_balance' => $trialBalance,
                'income_expenditure' => $incomeExpenditure,
                'balance_sheet' => $balanceSheet,
                'cash_book' => $this->accountLedger('1010', $fromDate, $toDate),
                'bank_book' => $this->accountLedger('1020', $fromDate, $toDate),
            ],
            'events' => $eventSummaries,
            'communications' => $commsByChannelAndStatus,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function accountLedger(string $accountCode, string $fromDate, string $toDate): array
    {
        $account = FinancialAccount::query()->where('code', $accountCode)->first();
        if ($account === null) {
            return ['lines' => collect(), 'net_pesewas' => 0, 'net_formatted' => 'GHS 0.00'];
        }

        $lines = JournalLine::query()
            ->with('entry')
            ->where('financial_account_id', $account->id)
            ->whereHas('entry', static fn ($q) => $q->whereBetween('entry_date', [$fromDate, $toDate]))
            ->orderBy('id')
            ->get();

        $net = (int) $lines->sum('debit_pesewas') - (int) $lines->sum('credit_pesewas');

        return [
            'account' => $account,
            'lines' => $lines,
            'net_pesewas' => $net,
            'net_formatted' => Money::fromPesewas($net)->format(),
        ];
    }

    /**
     * Builds tabular export dataset (title, headers, rows) for PDF or Excel-compatible CSV (Spec Section 14.4).
     *
     * @return array{title: string, period_label: string, headers: list<string>, rows: list<list<string>>}
     */
    public function buildExportDataset(string $reportType, string $fromDate, string $toDate): array
    {
        $periodLabel = "{$fromDate} to {$toDate}";

        return match ($reportType) {
            'membership_register' => [
                'title' => 'Approved & Pending Membership Register',
                'period_label' => 'As of '.now('Africa/Accra')->format('d M Y'),
                'headers' => ['Member Number', 'Full Name', 'Status', 'Phone', 'Email', 'Joined Date'],
                'rows' => Member::query()->orderBy('status')->orderBy('member_number')->get()->map(static fn (Member $m): array => [
                    $m->member_number ?? 'PENDING',
                    $m->fullName(),
                    strtoupper($m->status),
                    $m->phone ?? '',
                    $m->email ?? '',
                    $m->joined_at?->format('Y-m-d') ?? '',
                ])->all(),
            ],

            'member_arrears' => [
                'title' => 'Member Operational Dues Arrears Statement',
                'period_label' => 'As of '.now('Africa/Accra')->format('d M Y'),
                'headers' => ['Member Number', 'Member Name', 'Open Obligations', 'Outstanding (Pesewas)', 'Outstanding (GHS)'],
                'rows' => $this->balanceService->arrearsSummary()->map(static fn (array $r): array => [
                    (string) ($r['member']->member_number ?? ''),
                    $r['member']->fullName(),
                    (string) $r['obligations_count'],
                    (string) $r['outstanding_pesewas'],
                    $r['outstanding_money']->format(),
                ])->all(),
            ],

            'trial_balance' => (function () use ($toDate): array {
                $tb = $this->financialReporting->trialBalance(Carbon::parse($toDate));
                $rows = array_map(static fn (array $acc): array => [
                    $acc['code'],
                    $acc['name'],
                    strtoupper($acc['type']),
                    $acc['debit_formatted'],
                    $acc['credit_formatted'],
                ], $tb['accounts']);

                $rows[] = ['TOTAL', 'Trial Balance Totals', '', $tb['total_debit_formatted'], $tb['total_credit_formatted']];

                return [
                    'title' => 'Ledger-Reconciled Trial Balance',
                    'period_label' => "As of {$toDate}",
                    'headers' => ['Account Code', 'Account Name', 'Type', 'Debit (GHS)', 'Credit (GHS)'],
                    'rows' => $rows,
                ];
            })(),

            'income_expenditure' => (function () use ($fromDate, $toDate, $periodLabel): array {
                $ie = $this->financialReporting->incomeAndExpenditure($fromDate, $toDate);
                $rows = [];
                foreach ($ie['income'] as $inc) {
                    $rows[] = ['INCOME', $inc['code'], $inc['name'], $inc['formatted_amount']];
                }
                foreach ($ie['expenses'] as $exp) {
                    $rows[] = ['EXPENSE', $exp['code'], $exp['name'], $exp['formatted_amount']];
                }
                $rows[] = ['SUMMARY', '', 'Total Income', 'GHS '.$ie['total_income']['formatted_amount']];
                $rows[] = ['SUMMARY', '', 'Total Expenditure', 'GHS '.$ie['total_expenses']['formatted_amount']];
                $rows[] = ['SUMMARY', '', 'Net Surplus / (Deficit)', 'GHS '.$ie['net_surplus']['formatted_amount']];

                return [
                    'title' => 'Income and Expenditure Statement',
                    'period_label' => $periodLabel,
                    'headers' => ['Section', 'Account Code', 'Account Name', 'Amount (GHS)'],
                    'rows' => $rows,
                ];
            })(),

            'expenses_register' => [
                'title' => 'Expenses & Dual-Authorization Register',
                'period_label' => $periodLabel,
                'headers' => ['Reference', 'Date', 'Category', 'Payee', 'Channel', 'Status', 'Journal', 'Amount (GHS)'],
                'rows' => Expense::query()
                    ->with(['category', 'journalEntry'])
                    ->whereBetween('expense_date', [$fromDate, $toDate])
                    ->orderBy('expense_date')
                    ->get()
                    ->map(static fn (Expense $e): array => [
                        $e->reference,
                        $e->expense_date->format('Y-m-d'),
                        $e->category?->name ?? '',
                        $e->payee_name,
                        strtoupper($e->payment_channel),
                        strtoupper($e->status),
                        $e->journalEntry?->journal_number ?? '',
                        $e->amountMoney()->format(),
                    ])->all(),
            ],

            default => throw new \InvalidArgumentException("Unsupported report type [{$reportType}]."),
        };
    }

    public function exportCsv(string $reportType, string $fromDate, string $toDate, User $actor): StreamedResponse
    {
        $dataset = $this->buildExportDataset($reportType, $fromDate, $toDate);
        $branding = $this->settingService->branding();
        $generatedAt = now('Africa/Accra')->format('Y-m-d H:i:s T');

        $this->auditLogger->log(
            event: 'reports.exported_excel_csv',
            category: 'finance',
            description: "Exported [{$dataset['title']}] as Excel-compatible CSV ({$dataset['period_label']}).",
            actor: $actor,
        );

        $filename = "SCC-PYC-{$reportType}-".now('Africa/Accra')->format('Ymd-His').'.csv';

        return response()->streamDownload(static function () use ($dataset, $branding, $generatedAt, $actor): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for clean Excel opening
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [$branding['organization_name']]);
            fputcsv($handle, [$dataset['title']]);
            fputcsv($handle, ['Reporting Period:', $dataset['period_label'], 'Currency:', 'GHS']);
            fputcsv($handle, ['Generated At:', $generatedAt, 'Generated By:', $actor->name.' ('.$actor->primaryRoleName().')']);
            fputcsv($handle, []);
            fputcsv($handle, $dataset['headers']);

            foreach ($dataset['rows'] as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(string $reportType, string $fromDate, string $toDate, User $actor): Response
    {
        $dataset = $this->buildExportDataset($reportType, $fromDate, $toDate);
        $branding = $this->settingService->branding();
        $generatedAt = now('Africa/Accra')->format('d M Y, H:i:s T');

        $this->auditLogger->log(
            event: 'reports.exported_pdf',
            category: 'finance',
            description: "Exported [{$dataset['title']}] as PDF ({$dataset['period_label']}).",
            actor: $actor,
        );

        $pdf = Pdf::loadView('pdf.report', [
            'dataset' => $dataset,
            'branding' => $branding,
            'generatedAt' => $generatedAt,
            'generatedBy' => $actor->name.' ('.$actor->primaryRoleName().')',
        ])->setPaper('a4', 'portrait');

        $filename = "SCC-PYC-{$reportType}-".now('Africa/Accra')->format('Ymd-His').'.pdf';

        return $pdf->download($filename);
    }
}
