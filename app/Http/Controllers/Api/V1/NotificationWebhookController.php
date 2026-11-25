<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Notifications\Jobs\ProcessDeliveryWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider): JsonResponse
    {
        $secret = (string) config('notifications.sms.generic_http.webhook_secret', '');

        if ($secret === '') {
            return response()->json([
                'success' => false,
                'message' => 'Webhook verification secret is not configured.',
            ], 401);
        }

        $signature = (string) $request->header('X-Webhook-Signature', '');
        $rawBody = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $rawBody, $secret);

        if ($signature === '' || ! hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        $payload = $request->json()->all();
        $eventId = (string) ($payload['event_id'] ?? $payload['id'] ?? '');

        if ($eventId === '') {
            return response()->json([
                'success' => false,
                'message' => 'Missing provider event_id.',
            ], 422);
        }

        ProcessDeliveryWebhookJob::dispatch($provider, $eventId, $payload)
            ->onQueue((string) config('notifications.queues.webhooks', 'delivery_webhooks'));

        return response()->json([
            'success' => true,
            'message' => 'Delivery callback accepted.',
        ]);
    }
}
