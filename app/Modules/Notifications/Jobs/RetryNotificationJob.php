<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Jobs;

use App\Modules\Notifications\Enums\DeliveryStatus;
use App\Modules\Notifications\Models\NotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $deliveryId,
    ) {}

    public function handle(): void
    {
        $delivery = NotificationDelivery::query()->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        if (! in_array($delivery->status, [DeliveryStatus::Failed->value, DeliveryStatus::Retrying->value, DeliveryStatus::Unknown->value], true)) {
            return;
        }

        $queueName = (string) config('notifications.queues.default', 'notifications');

        if ($delivery->channel === 'sms') {
            SendSmsNotificationJob::dispatch($delivery->id)->onQueue($queueName);
        } elseif ($delivery->channel === 'email') {
            SendEmailNotificationJob::dispatch($delivery->id)->onQueue($queueName);
        }
    }
}
