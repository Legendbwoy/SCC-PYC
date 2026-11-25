<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Accounting\Models\FinancialAccount;
use App\Modules\Accounting\Models\FinancialPeriod;
use App\Modules\Expenses\Models\ExpenseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds the Section 8.2 Chart of Accounts, Expense Categories, and Calendar Financial Periods (Spec Section 18.2).
 */
class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => null, 'is_postable' => false],
            ['code' => '1010', 'name' => 'Cash in Hand', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000', 'is_postable' => true],
            ['code' => '1020', 'name' => 'PYC Bank Account', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000', 'is_postable' => true],
            ['code' => '1030', 'name' => 'Paystack Clearing', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000', 'is_postable' => true],

            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => null, 'is_postable' => false],
            ['code' => '2010', 'name' => 'Refunds Payable', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2000', 'is_postable' => true],

            ['code' => '3000', 'name' => 'Accumulated Funds', 'type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => null, 'is_postable' => true],

            ['code' => '4000', 'name' => 'Monthly Dues Income', 'type' => 'income', 'normal_balance' => 'credit', 'parent_code' => null, 'is_postable' => true],
            ['code' => '4100', 'name' => 'Event Contributions Income', 'type' => 'income', 'normal_balance' => 'credit', 'parent_code' => null, 'is_postable' => true],
            ['code' => '4200', 'name' => 'Donations Income', 'type' => 'income', 'normal_balance' => 'credit', 'parent_code' => null, 'is_postable' => true],

            ['code' => '5000', 'name' => 'Administrative Expenses', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => null, 'is_postable' => true],
            ['code' => '5100', 'name' => 'Event Expenses', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => null, 'is_postable' => true],
            ['code' => '5200', 'name' => 'Transportation Expenses', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => null, 'is_postable' => true],
            ['code' => '5300', 'name' => 'Welfare Expenses', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => null, 'is_postable' => true],
            ['code' => '5400', 'name' => 'Bank and Payment Charges', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => null, 'is_postable' => true],
        ];

        foreach ($accounts as $acc) {
            FinancialAccount::query()->updateOrCreate(
                ['code' => $acc['code']],
                [
                    'name' => $acc['name'],
                    'type' => $acc['type'],
                    'normal_balance' => $acc['normal_balance'],
                    'parent_code' => $acc['parent_code'],
                    'is_postable' => $acc['is_postable'],
                    'is_active' => true,
                ]
            );
        }

        $categories = [
            ['code' => 'admin', 'name' => 'Administrative & Stationery', 'account_code' => '5000'],
            ['code' => 'events', 'name' => 'Youth Events, Retreats & Programs', 'account_code' => '5100'],
            ['code' => 'transport', 'name' => 'Transportation & Logistics', 'account_code' => '5200'],
            ['code' => 'welfare', 'name' => 'Member Welfare & Visitation', 'account_code' => '5300'],
        ];

        foreach ($categories as $cat) {
            $finAcc = FinancialAccount::query()->where('code', $cat['account_code'])->firstOrFail();
            ExpenseCategory::query()->updateOrCreate(
                ['code' => $cat['code']],
                [
                    'name' => $cat['name'],
                    'financial_account_id' => $finAcc->id,
                    'is_active' => true,
                ]
            );
        }

        $year = (int) Carbon::now('Africa/Accra')->year;

        FinancialPeriod::query()->firstOrCreate(
            ['period_key' => (string) $year],
            [
                'period_type' => 'annual',
                'year' => $year,
                'month' => null,
                'starts_at' => "{$year}-01-01",
                'ends_at' => "{$year}-12-31",
                'status' => 'open',
            ]
        );

        for ($month = 1; $month <= 12; $month++) {
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $key = sprintf('%04d-%02d', $year, $month);

            FinancialPeriod::query()->firstOrCreate(
                ['period_key' => $key],
                [
                    'period_type' => 'monthly',
                    'year' => $year,
                    'month' => $month,
                    'starts_at' => $start->toDateString(),
                    'ends_at' => $end->toDateString(),
                    'status' => 'open',
                ]
            );
        }
    }
}
