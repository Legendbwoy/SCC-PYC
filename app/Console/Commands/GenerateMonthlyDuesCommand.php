<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Contributions\Services\MonthlyDuesService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateMonthlyDuesCommand extends Command
{
    protected $signature = 'pyc:generate-monthly-dues {period? : Monthly period in YYYY-MM format}';

    protected $description = 'Idempotently generate monthly PYC member dues obligations for eligible active members.';

    public function handle(MonthlyDuesService $monthlyDuesService): int
    {
        $tz = (string) config('pyc.organization.timezone', 'Africa/Accra');
        $period = $this->argument('period')
            ? trim((string) $this->argument('period'))
            : Carbon::now($tz)->format('Y-m');

        $result = $monthlyDuesService->generate($period);

        $this->info(
            "Monthly dues [{$result['period']}] ({$result['amount_pesewas']} pesewas): {$result['generated_count']} generated, {$result['skipped_count']} skipped."
        );

        return self::SUCCESS;
    }
}
