<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Jobs;

use App\Modules\Notifications\Contracts\SmsGatewayInterface;
use App\Modules\Notifications\DTOs\SmsMessage;
use App\Modules\Notifications\Enums\DeliveryStatus;
use App\Modules\Notifications\Models\NotificationDelivery;
use App\Modules\Notifications\Services\DeliveryTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 6;

    public function __construct(
        public readonly int $deliveryId,
    ) {}

    /**
     * Exponential retry schedule: 1m, 5m, 15m, 1h, 3h (Spec Section 10.14).
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        /** @var list<int> $schedule */
        $schedule = array_values((array) config('notifications.retry_backoff_seconds', [60, 300, 900, 3600, 10800]));

        return $schedule;
    }

    public function handle(
        SmsGatewayInterface $smsGateway,
        DeliveryTrackingService $trackingService,
    ): void {
        $delivery = NotificationDelivery::query()->with('log')->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        if (in_array($delivery->status, [
            DeliveryStatus::Accepted->value,
            DeliveryStatus::Sent->value,
            DeliveryStatus::Delivered->value,
            DeliveryStatus::Cancelled->value,
        ], true)) {
            return;
        }

        // Ambiguous timeout / prior provider reference reconciliation before resending (Spec 10.14 & 17.3)
        if ($delivery->attempts > 0 && $delivery->provider_reference !== null && $smsGateway->supportsDeliveryReports()) {
            $reconciledStatus = $smsGateway->getDeliveryStatus($delivery->provider_reference);

            if (in_array($reconciledStatus, [
                DeliveryStatus::Accepted->value,
                DeliveryStatus::Sent->value,
                DeliveryStatus::Delivered->value,
            ], true)) {
                $trackingService->markSubmitted(
                    delivery: $delivery,
                    status: (string) $reconciledStatus,
                    providerReference: $delivery->provider_reference,
                );

                return;
            }
        }

        $trackingService->markProcessing($delivery);

        $idempotencyKey = ($delivery->log?->idempotency_key ?? 'delivery-'.$delivery->id).':sms';

        $result = $smsGateway->send(new SmsMessage(
            recipient: $delivery->recipient_address,
            body: $delivery->rendered_body,
            idempotencyKey: $idempotencyKey,
        ));

        if ($result->accepted) {
            $trackingService->markSubmitted(
                delivery: $delivery,
                status: $result->status,
                providerReference: $result->providerReference,
                costPesewas: $result->costPesewas,
                costCurrency: $result->costCurrency,
                providerPayload: $result->rawResponse,
            );

            return;
        }

        $delaySeconds = $trackingService->handleFailure(
            delivery: $delivery,
            errorMessage: $result->errorMessage ?? 'SMS gateway rejected message.',
            retryable: $result->retryable,
            isAuthFailure: $result->isAuthFailure,
            isAmbiguousTimeout: $result->isAmbiguousTimeout,
            providerPayload: $result->rawResponse,
        );

        if ($delaySeconds !== null && config('queue.default') !== 'sync') {
            $this->release($delaySeconds);
        }
    }
}
