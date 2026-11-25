<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Providers\Sms;

use App\Modules\Notifications\Contracts\SmsGatewayInterface;
use App\Modules\Notifications\DTOs\SmsMessage;
use App\Modules\Notifications\DTOs\SmsResult;
use App\Modules\Notifications\Enums\DeliveryStatus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Provider-independent Generic HTTP SMS Gateway (Spec Section 10.4 & 10.6).
 */
class GenericHttpSmsGateway implements SmsGatewayInterface
{
    public function __construct(
        private readonly SmsResponseNormalizer $normalizer,
    ) {}

    public function send(SmsMessage $message): SmsResult
    {
        /** @var array<string, mixed> $config */
        $config = config('notifications.sms.generic_http', []);
        $endpoint = trim((string) ($config['endpoint'] ?? ''));

        if ($endpoint === '') {
            return new SmsResult(
                accepted: false,
                status: DeliveryStatus::Failed->value,
                errorMessage: 'SMS gateway endpoint is not configured.',
                retryable: false,
                isAuthFailure: true,
            );
        }

        $fields = (array) ($config['fields'] ?? []);
        $recipientField = (string) ($fields['recipient'] ?? 'to');
        $messageField = (string) ($fields['message'] ?? 'message');
        $senderField = (string) ($fields['sender'] ?? 'sender');
        $senderId = $message->senderId ?: (string) ($config['sender_id'] ?? 'SCC-PYC');

        $payload = [
            $recipientField => $message->recipient,
            $messageField => $message->body,
        ];

        if ($senderField !== '' && $senderId !== '') {
            $payload[$senderField] = $senderId;
        }

        $request = $this->buildAuthenticatedClient($config, $message->idempotencyKey);
        $method = strtoupper((string) ($config['method'] ?? 'POST'));
        $encoding = strtolower((string) ($config['encoding'] ?? 'json'));

        if ($encoding === 'form') {
            $request = $request->asForm();
        } else {
            $request = $request->asJson();
        }

        try {
            $response = match ($method) {
                'GET' => $request->get($endpoint, $payload),
                'PUT' => $request->put($endpoint, $payload),
                default => $request->post($endpoint, $payload),
            };

            return $this->normalizer->normalize(
                $response,
                (array) ($config['response_mapping'] ?? [])
            );
        } catch (ConnectionException $exception) {
            return new SmsResult(
                accepted: false,
                status: DeliveryStatus::Unknown->value,
                errorMessage: 'Ambiguous gateway timeout or connection error: '.$exception->getMessage(),
                retryable: true,
                isAmbiguousTimeout: true,
            );
        } catch (\Throwable $exception) {
            return new SmsResult(
                accepted: false,
                status: DeliveryStatus::Failed->value,
                errorMessage: 'SMS gateway unexpected error: '.$exception->getMessage(),
                retryable: false,
            );
        }
    }

    public function getDeliveryStatus(string $providerReference): ?string
    {
        /** @var array<string, mixed> $config */
        $config = config('notifications.sms.generic_http', []);
        $statusEndpoint = trim((string) ($config['status_endpoint'] ?? ''));

        if ($statusEndpoint === '' || $providerReference === '') {
            return null;
        }

        $url = str_contains($statusEndpoint, '{reference}')
            ? str_replace('{reference}', urlencode($providerReference), $statusEndpoint)
            : rtrim($statusEndpoint, '/').'/'.urlencode($providerReference);

        try {
            $response = $this->buildAuthenticatedClient($config)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();
            if (! is_array($json)) {
                return null;
            }

            $rawStatus = data_get($json, 'status') ?? data_get($json, 'data.status') ?? data_get($json, 'state');

            return is_string($rawStatus) ? $this->normalizer->normalizeDeliveryStatus($rawStatus) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function supportsDeliveryReports(): bool
    {
        return (bool) config('notifications.sms.generic_http.delivery_reports', false)
            || trim((string) config('notifications.sms.generic_http.status_endpoint', '')) !== '';
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function buildAuthenticatedClient(array $config, ?string $idempotencyKey = null): PendingRequest
    {
        $timeout = max(3, (int) ($config['timeout'] ?? 20));
        $request = Http::timeout($timeout)->acceptJson();

        /** @var array<string, string> $customHeaders */
        $customHeaders = (array) ($config['custom_headers'] ?? []);
        if ($customHeaders !== []) {
            $request = $request->withHeaders($customHeaders);
        }

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $request = $request->withHeaders(['Idempotency-Key' => $idempotencyKey]);
        }

        $authType = strtolower((string) ($config['auth_type'] ?? 'bearer'));
        $token = (string) ($config['api_token'] ?? '');

        return match ($authType) {
            'bearer' => $token !== '' ? $request->withToken($token) : $request,
            'api_key_header', 'custom_header' => $token !== ''
                ? $request->withHeaders([(string) ($config['auth_header_name'] ?? 'X-API-Key') => $token])
                : $request,
            'basic' => $request->withBasicAuth(
                (string) ($config['basic_username'] ?? ''),
                (string) ($config['basic_password'] ?? '')
            ),
            default => $request,
        };
    }
}
