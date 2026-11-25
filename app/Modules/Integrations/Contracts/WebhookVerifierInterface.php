<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Contracts;

interface WebhookVerifierInterface
{
    public function verifySignature(string $payload, string $signature): bool;
}
