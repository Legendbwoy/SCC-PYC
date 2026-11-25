<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Contracts;

use App\Modules\Notifications\DTOs\EmailMessage;

/**
 * Provider-independent Email contract (Spec Section 10.3).
 */
interface EmailProviderInterface
{
    public function send(EmailMessage $message): void;
}
