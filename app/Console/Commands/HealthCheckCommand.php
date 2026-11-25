<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Shared\OperationalHealthService;
use Illuminate\Console\Command;

class HealthCheckCommand extends Command
{
    protected $signature = 'pyc:health-check';

    protected $description = 'Run operational health checks on database, queues, webhooks, sync, and storage.';

    public function handle(OperationalHealthService $healthService): int
    {
        $report = $healthService->check();

        $this->table(
            ['Check', 'Status / Metric'],
            [
                ['Overall Status', $report['healthy'] ? 'HEALTHY' : 'DEGRADED'],
                ['Environment', $report['environment']],
                ['Database Connected', $report['database']['connected'] ? "Yes ({$report['database']['latency_ms']} ms)" : 'NO'],
                ['Failed Queue Jobs', (string) $report['queues']['failed_jobs']],
                ['Failed Payment Webhooks', (string) $report['exceptions_and_integrations']['failed_payment_webhooks']],
                ['Failed Notification Deliveries', (string) $report['exceptions_and_integrations']['failed_notification_deliveries']],
                ['Latest Google Sync', (string) $report['exceptions_and_integrations']['latest_google_sync_status']],
                ['Free Disk Space (MB)', (string) ($report['storage']['free_mb'] ?? 'unknown')],
            ]
        );

        return $report['healthy'] ? self::SUCCESS : self::FAILURE;
    }
}
