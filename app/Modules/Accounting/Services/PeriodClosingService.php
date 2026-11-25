<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Services;

use App\Models\User;
use App\Modules\Accounting\Models\FinancialPeriod;
use App\Support\Audit\AuditLogger;
use App\Support\Security\PycPermission;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PeriodClosingService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Resolves (or initializes an open calendar-month period if not yet seeded) for $date,
     * and enforces that neither the monthly period nor its parent annual period is closed.
     */
    public function resolveOpenMonthlyPeriodForPosting(CarbonInterface $date): FinancialPeriod
    {
        $year = (int) $date->year;
        $month = (int) $date->month;
        $periodKey = sprintf('%04d-%02d', $year, $month);

        $annualClosed = FinancialPeriod::query()
            ->where('period_key', (string) $year)
            ->where('period_type', 'annual')
            ->where('status', 'closed')
            ->exists();

        if ($annualClosed) {
            throw new InvalidArgumentException("Financial year [{$year}] is closed and blocks ordinary posting.");
        }

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $period = FinancialPeriod::query()->firstOrCreate(
            ['period_key' => $periodKey],
            [
                'period_type' => 'monthly',
                'year' => $year,
                'month' => $month,
                'starts_at' => $start->toDateString(),
                'ends_at' => $end->toDateString(),
                'status' => 'open',
            ]
        );

        $locked = FinancialPeriod::query()->lockForUpdate()->findOrFail($period->id);

        if (! $locked->isOpen()) {
            throw new InvalidArgumentException("Financial period [{$periodKey}] is closed and blocks ordinary posting.");
        }

        return $locked;
    }

    public function closePeriod(FinancialPeriod $period, User $actor, ?string $notes = null): FinancialPeriod
    {
        if (! $actor->can(PycPermission::AccountingClosePeriod->value)) {
            throw new AuthorizationException('You are not authorized to close financial periods.');
        }

        return DB::transaction(function () use ($period, $actor, $notes): FinancialPeriod {
            $locked = FinancialPeriod::query()->lockForUpdate()->findOrFail($period->id);

            if (! $locked->isOpen()) {
                throw new InvalidArgumentException("Financial period [{$locked->period_key}] is already closed.");
            }

            $locked->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => $actor->id,
                'closure_notes' => $notes,
            ]);

            $this->auditLogger->log(
                event: 'accounting.period_closed',
                category: 'finance',
                description: "Closed financial period [{$locked->period_key}].",
                auditable: $locked,
                newValues: ['status' => 'closed', 'closure_notes' => $notes],
                actor: $actor,
            );

            return $locked;
        });
    }
}
