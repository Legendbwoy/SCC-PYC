<?php

declare(strict_types=1);

namespace App\Modules\Notifications\DTOs;

final readonly class SmsMessage
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $recipient,
        public string $body,
        public ?string $senderId = null,
        public ?string $idempotencyKey = null,
        public array $metadata = [],
    ) {}
}
