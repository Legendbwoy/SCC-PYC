<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Modules\Integrations\Contracts\WebhookVerifierInterface;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Isolated Paystack REST API client & HMAC-SHA512 webhook signature verifier (Spec Section 7.1, 7.3 & 13.7).
 */
class PaystackService implements PaymentGatewayInterface, WebhookVerifierInterface
{
    /**
     * @param  array<string, mixed>  $metadata
     * @return array{authorization_url: string, access_code: string, reference: string}
     */
    public function initializeTransaction(
        string $reference,
        string $email,
        int $amountPesewas,
        string $currency = 'GHS',
        array $metadata = [],
    ): array {
        $secretKey = trim((string) config('pyc.payments.paystack.secret_key', ''));
        $baseUrl = rtrim((string) config('pyc.payments.paystack.base_url', 'https://api.paystack.co'), '/');

        // Local sandbox fallback only when no API key is configured in non-production
        if ($secretKey === '' && ! app()->isProduction()) {
            return [
                'authorization_url' => route('member.payments.callback', ['reference' => $reference]),
                'access_code' => 'LOCAL_SANDBOX_'.$reference,
                'reference' => $reference,
            ];
        }

        if ($secretKey === '') {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        $response = Http::withToken($secretKey)
            ->timeout(20)
            ->acceptJson()
            ->post("{$baseUrl}/transaction/initialize", [
                'reference' => $reference,
                'email' => $email,
                'amount' => $amountPesewas, // 1 GHS = 100 pesewas (matches Paystack minor unit)
                'currency' => strtoupper($currency),
                'callback_url' => route('member.payments.callback'),
                'metadata' => $metadata,
            ]);

        if (! $response->successful() || ! (bool) $response->json('status')) {
            throw new RuntimeException(
                'Paystack initialization failed: '.(string) ($response->json('message') ?? "HTTP {$response->status()}")
            );
        }

        return [
            'authorization_url' => (string) $response->json('data.authorization_url'),
            'access_code' => (string) $response->json('data.access_code'),
            'reference' => (string) ($response->json('data.reference') ?: $reference),
        ];
    }

    /**
     * @return array{verified: bool, status: string, amount_pesewas: int, currency: string, provider_reference: string, raw: array<string, mixed>}
     */
    public function verifyTransaction(string $reference): array
    {
        $secretKey = trim((string) config('pyc.payments.paystack.secret_key', ''));
        $baseUrl = rtrim((string) config('pyc.payments.paystack.base_url', 'https://api.paystack.co'), '/');

        if ($secretKey === '') {
            throw new RuntimeException('Paystack secret key is not configured for server-side verification.');
        }

        $response = Http::withToken($secretKey)
            ->timeout(20)
            ->acceptJson()
            ->get("{$baseUrl}/transaction/verify/".rawurlencode($reference));

        $json = $response->json();
        $raw = is_array($json) ? $json : [];

        if (! $response->successful() || ! (bool) data_get($raw, 'status')) {
            return [
                'verified' => false,
                'status' => 'failed',
                'amount_pesewas' => 0,
                'currency' => '',
                'provider_reference' => $reference,
                'raw' => $raw,
            ];
        }

        $txStatus = strtolower((string) data_get($raw, 'data.status', ''));
        $amountPesewas = (int) data_get($raw, 'data.amount', 0);
        $currency = strtoupper((string) data_get($raw, 'data.currency', ''));
        $providerId = (string) (data_get($raw, 'data.id') ?: data_get($raw, 'data.reference', $reference));

        return [
            'verified' => $txStatus === 'success',
            'status' => $txStatus,
            'amount_pesewas' => $amountPesewas,
            'currency' => $currency,
            'provider_reference' => $providerId,
            'raw' => $raw,
        ];
    }

    public function verifySignature(string $payload, string $signature): bool
    {
        $secret = trim((string) (
            config('pyc.payments.paystack.webhook_secret')
            ?: config('pyc.payments.paystack.secret_key', '')
        ));

        if ($secret === '' || trim($signature) === '') {
            return false;
        }

        $expected = hash_hmac('sha512', $payload, $secret);

        return hash_equals($expected, trim($signature));
    }
}
