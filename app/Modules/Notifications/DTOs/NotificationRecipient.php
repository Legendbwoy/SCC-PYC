<?php

declare(strict_types=1);

namespace App\Modules\Notifications\DTOs;

final readonly class NotificationRecipient
{
    public function __construct(
        public ?int $userId = null,
        public ?int $memberId = null,
        public string $name = 'Member',
        public ?string $email = null,
        public ?string $phone = null,
    ) {}
}
