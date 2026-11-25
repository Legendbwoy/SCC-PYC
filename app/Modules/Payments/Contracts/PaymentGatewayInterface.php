<?php

declare(strict_types=1);

namespace App\Modules\Payments\Contracts;

/**
 * Online payment provider contract for server-side checkout initialization and reference verification (Spec Section 7.1 & 7.3).
 */
interface PaymentGatewayInterface
{
    /**
     * @param  array<string, mixed>  $metadata
     * @return array{authorization_url: string, access_code: string, reference: string}
     */
    public function initializeTransaction(string $reference, string $email, int $amountPesewas, string $currency = 'GHS', array $metadata = []): array;

    /**
     * @return array{verified: bool, status: string, amount_pesewas: int, currency: string, provider_reference: string, raw: array<string, mixed>}
     */
    public function verifyTransaction(string $reference): array;
}
