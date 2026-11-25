<?php

declare(strict_types=1);

namespace App\Modules\Notifications\DTOs;

final readonly class DispatchNotificationData
{
    /**
     * @param  list<string>  $channels
     * @param  array<string, scalar|null>  $variables
     */
    public function __construct(
        public string $idempotencyKey,
        public string $templateCode,
        public NotificationRecipient $recipient,
        public array $variables = [],
        public array $channels = ['sms', 'email', 'in_app'],
        public ?string $eventName = null,
        public ?string $relatedType = null,
        public ?int $relatedId = null,
        public ?int $campaignId = null,
        public ?int $initiatedByUserId = null,
        public ?string $priorityQueue = null,
        public bool $failSafely = true,
    ) {}
}
