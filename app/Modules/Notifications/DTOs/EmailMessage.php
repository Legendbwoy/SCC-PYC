<?php

declare(strict_types=1);

namespace App\Modules\Notifications\DTOs;

final readonly class EmailMessage
{
    /**
     * @param  list<string>  $attachmentPaths
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $recipientEmail,
        public string $recipientName,
        public string $subject,
        public string $htmlBody,
        public ?string $textBody = null,
        public array $attachmentPaths = [],
        public ?string $idempotencyKey = null,
        public array $metadata = [],
    ) {}
}
