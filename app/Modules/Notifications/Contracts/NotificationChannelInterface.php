<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Contracts;

interface NotificationChannelInterface
{
    public function channelName(): string;

    public function isEnabled(): bool;
}
