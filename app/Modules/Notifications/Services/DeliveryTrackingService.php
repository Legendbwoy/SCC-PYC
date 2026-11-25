<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\Enums\DeliveryStatus;
use App\Modules\Notifications\Models\NotificationDelivery;
use App\Modules\Notifications\Models\NotificationLog;
use App\Modules\Notifications\Models\NotificationWebhook;
use App\Modules\Notifications\Providers\Sms\SmsResponseNormalizer;
use App\Support\Audit\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tracks independent channel delivery lifecycles, retries, auth alerts, and webhook callbacks (Spec 10.12–10.14).
 */
class DeliveryTrackingService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly SmsResponseNormalizer $smsNormalizer,
    ) {}

    public function markProcessing(NotificationDelivery $delivery): void
    {
        $delivery->update([
            'status' => DeliveryStatus::Processing->value,
            'attempts' => $delivery->attempts + 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $providerPayload
     */
    public function markSubmitted(
        NotificationDelivery $delivery,
        string $status,
        ?string $providerReference = null,
        ?int $costPesewas = null,
        ?string $costCurrency = null,
        array $providerPayload = [],
    ): void {
        $delivery->update([
            'status' => $status,
            'provider_reference' => $providerReference ?? $delivery->provider_reference,
            'cost_pesewas' => $costPesewas ?? $delivery->cost_pesewas,
            'cost_currency' => $costCurrency ?? $delivery->cost_currency,
            'provider_payload' => $providerPayload !== [] ? $providerPayload : $delivery->provider_payload,
            'is_retryable' => false,
            'next_retry_at' => null,
            'ambiguous_timeout_at' => null,
            'last_error' => null,
            'sent_at' => $delivery->sent_at ?? now(),
            'delivered_at' => $status === DeliveryStatus::Delivered->value ? now() : $delivery->delivered_at,
        ]);

        $this->syncParentLogStatus($delivery->notification_log_id);
    }

    public function handleFailure(
        NotificationDelivery $delivery,
        string $errorMessage,
        bool $retryable,
        bool $isAuthFailure = false,
        bool $isAmbiguousTimeout = false,
        array $providerPayload = [],
    ): ?int {
        if ($isAuthFailure) {
            Log::critical('Notification provider authentication/configuration failure', [
                'delivery_id' => $delivery->id,
                'channel' => $delivery->channel,
                'provider' => $delivery->provider,
                'error' => $errorMessage,
            ]);

            $this->auditLogger->log(
                event: 'notifications.provider_auth_alert',
                category: 'security',
                description: "Alert: Notification provider [{$delivery->provider}] ({$delivery->channel}) reported authentication or configuration failure.",
                auditable: $delivery,
                metadata: [
                    'channel' => $delivery->channel,
                    'provider' => $delivery->provider,
                    'error' => $errorMessage,
                ],
            );
        }

        $retryLimit = (int) config('notifications.retry_limit', 5);
        /** @var list<int> $backoffs */
        $backoffs = array_values((array) config('notifications.retry_backoff_seconds', [60, 300, 900, 3600, 10800]));

        // $delivery->attempts was already incremented in markProcessing() (1 = initial attempt, 2 = retry 1, ..., 6 = retry 5)
        $retriesUsed = max(0, $delivery->attempts - 1);

        if ($retryable && ! $isAuthFailure && $retriesUsed < $retryLimit) {
            $delaySeconds = $backoffs[$retriesUsed] ?? end($backoffs) ?: 3600;

            $delivery->update([
                'status' => DeliveryStatus::Retrying->value,
                'last_error' => $errorMessage,
                'is_retryable' => true,
                'next_retry_at' => now()->addSeconds($delaySeconds),
                'ambiguous_timeout_at' => $isAmbiguousTimeout ? now() : $delivery->ambiguous_timeout_at,
                'provider_payload' => $providerPayload !== [] ? $providerPayload : $delivery->provider_payload,
            ]);

            $this->syncParentLogStatus($delivery->notification_log_id);

            return $delaySeconds;
        }

        $delivery->update([
            'status' => DeliveryStatus::Failed->value,
            'last_error' => $errorMessage,
            'is_retryable' => false,
            'next_retry_at' => null,
            'failed_at' => now(),
            'provider_payload' => $providerPayload !== [] ? $providerPayload : $delivery->provider_payload,
        ]);

        $this->syncParentLogStatus($delivery->notification_log_id);

        return null;
    }

    /**
     * Deduplicates and processes a provider delivery webhook callback (Spec Section 10.11 & 17.3).
     *
     * @param  array<string, mixed>  $payload
     */
    public function processDeliveryWebhook(string $provider, string $providerEventId, array $payload): NotificationWebhook
    {
        $payloadHash = hash('sha256', json_encode($payload) ?: '');

        return DB::transaction(function () use ($provider, $providerEventId, $payloadHash, $payload): NotificationWebhook {
            $existing = NotificationWebhook::query()
                ->where('provider', $provider)
                ->where('provider_event_id', $providerEventId)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            try {
                $webhook = NotificationWebhook::query()->create([
                    'provider' => $provider,
                    'provider_event_id' => $providerEventId,
                    'payload_hash' => $payloadHash,
                    'payload' => $payload,
                    'processing_status' => 'received',
                ]);
            } catch (QueryException) {
                return NotificationWebhook::query()
                    ->where('provider', $provider)
                    ->where('provider_event_id', $providerEventId)
                    ->firstOrFail();
            }

            $providerRef = (string) (data_get($payload, 'provider_reference')
                ?? data_get($payload, 'message_id')
                ?? data_get($payload, 'reference')
                ?? '');
            $rawStatus = (string) (data_get($payload, 'status') ?? data_get($payload, 'state') ?? '');

            if ($providerRef === '' || $rawStatus === '') {
                $webhook->update([
                    'processing_status' => 'ignored',
                    'processed_at' => now(),
                    'error_message' => 'Missing provider_reference or status in callback payload.',
                ]);

                return $webhook;
            }

            $delivery = NotificationDelivery::query()
                ->where('provider_reference', $providerRef)
                ->lockForUpdate()
                ->first();

            if ($delivery === null) {
                $webhook->update([
                    'processing_status' => 'ignored',
                    'processed_at' => now(),
                    'error_message' => "No matching delivery found for reference [{$providerRef}].",
                ]);

                return $webhook;
            }

            $normalizedStatus = $this->smsNormalizer->normalizeDeliveryStatus($rawStatus);

            $delivery->update([
                'status' => $normalizedStatus,
                'delivered_at' => $normalizedStatus === DeliveryStatus::Delivered->value ? now() : $delivery->delivered_at,
                'failed_at' => $normalizedStatus === DeliveryStatus::Failed->value ? now() : $delivery->failed_at,
                'last_error' => $normalizedStatus === DeliveryStatus::Failed->value
                    ? (string) (data_get($payload, 'error') ?? 'Provider reported delivery failure.')
                    : $delivery->last_error,
            ]);

            $this->syncParentLogStatus($delivery->notification_log_id);

            $webhook->update([
                'processing_status' => 'processed',
                'processed_at' => now(),
            ]);

            return $webhook;
        });
    }

    public function syncParentLogStatus(int $notificationLogId): void
    {
        $log = NotificationLog::query()->with('deliveries')->find($notificationLogId);
        if ($log === null) {
            return;
        }

        $statuses = $log->deliveries->pluck('status')->all();

        if ($statuses === []) {
            $summary = 'suppressed';
        } elseif (in_array(DeliveryStatus::Processing->value, $statuses, true) || in_array(DeliveryStatus::Retrying->value, $statuses, true)) {
            $summary = 'processing';
        } elseif (in_array(DeliveryStatus::Delivered->value, $statuses, true) || in_array(DeliveryStatus::Sent->value, $statuses, true) || in_array(DeliveryStatus::Accepted->value, $statuses, true)) {
            $summary = in_array(DeliveryStatus::Failed->value, $statuses, true) ? 'partial' : 'sent';
        } elseif (in_array(DeliveryStatus::Queued->value, $statuses, true)) {
            $summary = 'queued';
        } else {
            $summary = DeliveryStatus::Failed->value;
        }

        $log->update(['status' => $summary]);
    }
}
