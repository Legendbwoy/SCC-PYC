<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Contracts;

use App\Modules\Notifications\DTOs\SmsMessage;
use App\Modules\Notifications\DTOs\SmsResult;

/**
 * Provider-independent SMS contract (Spec Section 10.3).
 */
interface SmsGatewayInterface
{
    public function send(SmsMessage $message): SmsResult;

    public function getDeliveryStatus(string $providerReference): ?string;

    public function supportsDeliveryReports(): bool;
}
