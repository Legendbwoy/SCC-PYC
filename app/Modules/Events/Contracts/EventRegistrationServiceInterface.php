<?php

declare(strict_types=1);

namespace App\Modules\Events\Contracts;

interface EventRegistrationServiceInterface
{
    public function registerMember(int $eventId, int $memberId): void;

    public function cancelRegistration(int $eventId, int $memberId, ?string $reason = null): void;
}
