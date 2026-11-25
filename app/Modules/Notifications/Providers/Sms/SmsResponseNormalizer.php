<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Providers\Sms;

use App\Modules\Notifications\DTOs\SmsResult;
use App\Modules\Notifications\Enums\DeliveryStatus;
use Illuminate\Http\Client\Response;

class SmsResponseNormalizer
{
    /**
     * @param  array<string, mixed>  $mappingConfig
     */
    public function normalize(Response $response, array $mappingConfig = []): SmsResult
    {
        $statusCode = $response->status();
        $json = $response->json();
        $payload = is_array($json) ? $json : ['raw_body' => mb_substr($response->body(), 0, 1000)];

        /** @var list<string> $refPaths */
        $refPaths = $mappingConfig['reference_paths'] ?? ['message_id', 'data.message_id', 'id', 'data.id', 'reference'];
        $providerRef = $this->firstScalar($payload, $refPaths);

        /** @var list<string> $statusPaths */
        $statusPaths = $mappingConfig['status_paths'] ?? ['status', 'data.status', 'state'];
        $rawStatus = strtolower((string) ($this->firstScalar($payload, $statusPaths) ?? ''));

        $costPath = (string) ($mappingConfig['cost_pesewas_path'] ?? 'cost_pesewas');
        $rawCost = data_get($payload, $costPath);
        $costPesewas = is_numeric($rawCost) ? (int) $rawCost : null;

        if ($response->successful()) {
            $mappedStatus = match ($rawStatus) {
                'sent', 'submitted' => DeliveryStatus::Sent->value,
                'delivered' => DeliveryStatus::Delivered->value,
                default => DeliveryStatus::Accepted->value,
            };

            return new SmsResult(
                accepted: true,
                status: $mappedStatus,
                providerReference: $providerRef,
                rawResponse: $payload,
                costPesewas: $costPesewas,
                costCurrency: $costPesewas !== null ? 'GHS' : null,
            );
        }

        $isAuthFailure = in_array($statusCode, [401, 403], true);
        $isTransient = $statusCode === 429 || $statusCode >= 500 || $statusCode === 408;

        $errorMsg = (string) (
            $this->firstScalar($payload, ['message', 'error', 'error.message', 'detail'])
            ?? "SMS gateway returned HTTP {$statusCode}."
        );

        return new SmsResult(
            accepted: false,
            status: $isTransient ? DeliveryStatus::Retrying->value : DeliveryStatus::Failed->value,
            providerReference: $providerRef,
            errorMessage: $errorMsg,
            retryable: $isTransient,
            rawResponse: $payload,
            isAuthFailure: $isAuthFailure,
        );
    }

    public function normalizeDeliveryStatus(string $rawStatus): string
    {
        return match (strtolower(trim($rawStatus))) {
            'queued', 'pending' => DeliveryStatus::Queued->value,
            'accepted' => DeliveryStatus::Accepted->value,
            'sent', 'submitted', 'dispatched' => DeliveryStatus::Sent->value,
            'delivered', 'dlvrd', 'success' => DeliveryStatus::Delivered->value,
            'failed', 'undeliverable', 'rejected', 'expired', 'bounced' => DeliveryStatus::Failed->value,
            default => DeliveryStatus::Unknown->value,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $paths
     */
    private function firstScalar(array $payload, array $paths): ?string
    {
        foreach ($paths as $path) {
            $val = data_get($payload, $path);
            if (is_string($val) && trim($val) !== '') {
                return trim($val);
            }
            if (is_int($val) || is_float($val)) {
                return (string) $val;
            }
        }

        return null;
    }
}
