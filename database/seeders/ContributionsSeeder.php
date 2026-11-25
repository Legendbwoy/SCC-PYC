<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Contributions\Models\ContributionCampaign;
use App\Modules\Contributions\Models\ContributionType;
use Illuminate\Database\Seeder;

/**
 * Seeds contribution types and the confirmed GHS 10.00 (1,000 pesewas) Monthly Youth Dues campaign (Spec Section 6.1 & 18.2).
 */
class ContributionsSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => 'monthly_dues',
                'name' => 'Monthly Youth Dues',
                'description' => 'Mandatory recurring monthly PYC member dues',
                'default_income_account_code' => '4000',
                'is_recurring' => true,
                'is_mandatory' => true,
            ],
            [
                'code' => 'event_contribution',
                'name' => 'Event Contribution',
                'description' => 'Contributions linked to parish youth events and retreats',
                'default_income_account_code' => '4100',
                'is_recurring' => false,
                'is_mandatory' => false,
            ],
            [
                'code' => 'special_collection',
                'name' => 'Special Collection / Levy',
                'description' => 'Approved special levies and project collections',
                'default_income_account_code' => '4200',
                'is_recurring' => false,
                'is_mandatory' => false,
            ],
            [
                'code' => 'donation',
                'name' => 'Voluntary Donation',
                'description' => 'Freewill donations and sponsorships supporting PYC',
                'default_income_account_code' => '4200',
                'is_recurring' => false,
                'is_mandatory' => false,
            ],
        ];

        foreach ($types as $item) {
            ContributionType::query()->updateOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'default_income_account_code' => $item['default_income_account_code'],
                    'is_recurring' => $item['is_recurring'],
                    'is_mandatory' => $item['is_mandatory'],
                    'is_active' => true,
                ]
            );
        }

        $monthlyDuesType = ContributionType::query()->where('code', 'monthly_dues')->firstOrFail();

        ContributionCampaign::query()->firstOrCreate(
            ['code' => 'monthly-youth-dues'],
            [
                'contribution_type_id' => $monthlyDuesType->id,
                'title' => 'Monthly Youth Dues',
                'description' => 'Confirmed PYC monthly membership dues at GHS 10.00 (1,000 pesewas) per month.',
                'amount_pesewas' => (int) config('pyc.finance.monthly_dues_pesewas', 1000),
                'currency' => 'GHS',
                'frequency' => 'monthly',
                'effective_from' => '2026-01-01',
                'effective_to' => null,
                'due_day_of_month' => 28,
                'allow_partial_payments' => true,
                'allow_advance_payments' => true,
                'status' => 'active',
            ]
        );
    }
}
