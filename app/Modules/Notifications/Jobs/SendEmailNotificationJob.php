<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Jobs;

use App\Modules\Notifications\Contracts\EmailProviderInterface;
use App\Modules\Notifications\DTOs\EmailMessage;
use App\Modules\Notifications\Enums\DeliveryStatus;
use App\Modules\Notifications\Exceptions\NotificationProviderException;
use App\Modules\Notifications\Models\NotificationDelivery;
use App\Modules\Notifications\Services\DeliveryTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailNotificationJob implements ShouldQueue
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
     * @return list<int>
     */
    public function backoff(): array
    {
        /** @var list<int> $schedule */
        $schedule = array_values((array) config('notifications.retry_backoff_seconds', [60, 300, 900, 3600, 10800]));

        return $schedule;
    }

    public function handle(
        EmailProviderInterface $emailProvider,
        DeliveryTrackingService $trackingService,
    ): void {
        $delivery = NotificationDelivery::query()->with('log')->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        if (in_array($delivery->status, [
            DeliveryStatus::Sent->value,
            DeliveryStatus::Delivered->value,
            DeliveryStatus::Cancelled->value,
        ], true)) {
            return;
        }

        $trackingService->markProcessing($delivery);

        try {
            $emailProvider->send(new EmailMessage(
                recipientEmail: $delivery->recipient_address,
                recipientName: $delivery->recipient_name ?? 'PYC Member',
                subject: $delivery->rendered_subject ?? 'St. Cecilia Parish Youth Council Notice',
                htmlBody: $delivery->rendered_body,
                textBody: trim(strip_tags($delivery->rendered_body)),
                idempotencyKey: ($delivery->log?->idempotency_key ?? 'delivery-'.$delivery->id).':email',
            ));

            // Spec Section 10.12: SMTP handoff records 'sent' (server accepted), never falsely 'delivered'.
            $trackingService->markSubmitted(
                delivery: $delivery,
                status: DeliveryStatus::Sent->value,
            );
        } catch (NotificationProviderException $exception) {
            $delaySeconds = $trackingService->handleFailure(
                delivery: $delivery,
                errorMessage: $exception->getMessage(),
                retryable: $exception->retryable,
                isAuthFailure: $exception->isAuthFailure,
                isAmbiguousTimeout: $exception->isAmbiguousTimeout,
            );

            if ($delaySeconds !== null && config('queue.default') !== 'sync') {
                $this->release($delaySeconds);
            }
        }
    }
}
