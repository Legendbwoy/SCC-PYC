<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\NotificationApiController;
use App\Http\Controllers\Api\V1\NotificationWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PYC API v1 Routes (Spec Section 13.1 & 13.6)
|--------------------------------------------------------------------------
*/
Route::middleware(['throttle:api'])->group(function (): void {
    Route::get('/status', static fn () => response()->json([
        'success' => true,
        'message' => 'PYC API v1 operational.',
        'data' => [
            'organization' => config('pyc.organization.name'),
            'currency' => config('pyc.organization.currency'),
            'timezone' => config('pyc.organization.timezone'),
            'version' => '1.2.0-n1',
        ],
    ]))->name('api.v1.status');

    // Authenticated provider delivery webhooks (HMAC signature verified)
    Route::post('/webhooks/notifications/{provider}', NotificationWebhookController::class)
        ->whereAlphaNumeric('provider')
        ->name('api.v1.webhooks.notifications');

    // Session-authenticated portal API endpoints (Spec Section 13.1 & 13.6)
    Route::middleware(['web', 'auth', 'pyc.active', 'pyc.2fa'])->prefix('notifications')->group(function (): void {
        Route::get('/', [NotificationApiController::class, 'index']);
        Route::get('/templates', [NotificationApiController::class, 'templates']);
        Route::post('/templates', [NotificationApiController::class, 'storeTemplate']);
        Route::patch('/templates/{template}', [NotificationApiController::class, 'updateTemplate']);
        Route::post('/test', [NotificationApiController::class, 'sendTest'])->middleware('throttle:notifications-test');
        Route::post('/campaigns', [NotificationApiController::class, 'storeCampaign'])->middleware('throttle:notifications-bulk');
        Route::post('/campaigns/{campaign}/schedule', [NotificationApiController::class, 'scheduleCampaign']);
        Route::post('/campaigns/{campaign}/cancel', [NotificationApiController::class, 'cancelCampaign']);
        Route::get('/deliveries', [NotificationApiController::class, 'deliveries']);
        Route::post('/deliveries/{delivery}/retry', [NotificationApiController::class, 'retryDelivery']);
        Route::get('/preferences', [NotificationApiController::class, 'preferences']);
        Route::patch('/preferences', [NotificationApiController::class, 'updatePreferences']);
        Route::get('/providers/status', [NotificationApiController::class, 'providerStatus']);
    });
});
