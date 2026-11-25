<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Signed Paystack webhook endpoint (Spec Section 13.7: POST /api/v1/webhooks/paystack).
 */
class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentService $paymentService): JsonResponse
    {
        $signature = (string) $request->header('x-paystack-signature', '');
        $rawPayload = $request->getContent();

        try {
            $webhook = $paymentService->handlePaystackWebhook($rawPayload, $signature);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed.',
                'data' => [
                    'processing_status' => $webhook->processing_status,
                ],
            ]);
        } catch (AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Paystack webhook signature.',
            ], 401);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
