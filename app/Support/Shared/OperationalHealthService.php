<?php

declare(strict_types=1);

namespace App\Support\Shared;

use App\Modules\Integrations\Models\SyncLog;
use App\Modules\Notifications\Models\NotificationDelivery;
use App\Modules\Payments\Models\PaymentWebhook;
use Illuminate\Support\Facades\DB;

/**
 * Operational monitoring & health diagnostics (Spec Section 15.5).
 */
class OperationalHealthService
{
    /**
     * @return array<string, mixed>
     */
    public function check(): array
    {
        $dbHealthy = false;
        $dbLatencyMs = null;

        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $dbLatencyMs = round((microtime(true) - $start) * 1000, 2);
            $dbHealthy = true;
        } catch (\Throwable) {
            $dbHealthy = false;
        }

        $queueBacklog = $dbHealthy
            ? DB::table('jobs')->selectRaw('queue, count(*) as total')->groupBy('queue')->pluck('total', 'queue')->all()
            : [];

        $failedJobsCount = $dbHealthy ? (int) DB::table('failed_jobs')->count() : 0;
        $failedPaymentWebhooks = $dbHealthy ? PaymentWebhook::query()->where('processing_status', 'failed')->count() : 0;
        $failedDeliveries = $dbHealthy ? NotificationDelivery::query()->where('status', 'failed')->count() : 0;
        $latestGoogleSync = $dbHealthy ? SyncLog::query()->where('provider', 'google_sheets')->latest('id')->first() : null;

        $storagePath = storage_path();
        $freeBytes = @disk_free_space($storagePath);
        $freeMb = is_float($freeBytes) || is_int($freeBytes) ? round($freeBytes / 1048576, 1) : null;

        $productionConfigSafe = ! app()->isProduction() || ! (bool) config('app.debug');

        $overallHealthy = $dbHealthy && $productionConfigSafe;

        return [
            'healthy' => $overallHealthy,
            'checked_at' => now('Africa/Accra')->toIso8601String(),
            'environment' => app()->environment(),
            'database' => [
                'connected' => $dbHealthy,
                'latency_ms' => $dbLatencyMs,
            ],
            'queues' => [
                'backlog_by_queue' => $queueBacklog,
                'failed_jobs' => $failedJobsCount,
            ],
            'exceptions_and_integrations' => [
                'failed_payment_webhooks' => $failedPaymentWebhooks,
                'failed_notification_deliveries' => $failedDeliveries,
                'latest_google_sync_status' => $latestGoogleSync?->status ?? 'never_run',
            ],
            'storage' => [
                'free_mb' => $freeMb,
            ],
            'security' => [
                'production_debug_safe' => $productionConfigSafe,
                'admin_2fa_enforced' => (bool) config('pyc.security.enforce_admin_2fa', true),
                'maker_checker_enforced' => (bool) config('pyc.approvals.enforce_maker_checker', true),
            ],
        ];
    }
}
