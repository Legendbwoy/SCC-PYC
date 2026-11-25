<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\MembershipApiController;
use App\Http\Controllers\Api\V1\NotificationApiController;
use App\Http\Controllers\Api\V1\NotificationWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PYC API v1 Routes (Spec Section 13.1, 13.2 & 13.6)
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
            'version' => '1.2.0-sprint2',
        ],
    ]))->name('api.v1.status');

    Route::post('/webhooks/notifications/{provider}', NotificationWebhookController::class)
        ->whereAlphaNumeric('provider')
        ->name('api.v1.webhooks.notifications');

    Route::middleware(['web', 'auth', 'pyc.active', 'pyc.2fa'])->group(function (): void {
        // Section 13.2 Membership & Google Sync endpoints
        Route::get('/members', [MembershipApiController::class, 'index']);
        Route::get('/members/{member}', [MembershipApiController::class, 'show']);
        Route::post('/members', [MembershipApiController::class, 'store']);
        Route::patch('/members/{member}', [MembershipApiController::class, 'update']);
        Route::post('/members/{member}/approve', [MembershipApiController::class, 'approve']);
        Route::post('/members/{member}/reject', [MembershipApiController::class, 'reject']);
        Route::post('/members/{member}/invite', [MembershipApiController::class, 'invite']);

        Route::get('/integrations/google/status', [MembershipApiController::class, 'googleStatus']);
        Route::post('/integrations/google/sync', [MembershipApiController::class, 'googleSync']);
        Route::get('/integrations/google/logs', [MembershipApiController::class, 'googleLogs']);

        // Section 13.6 Notification endpoints
        Route::prefix('notifications')->group(function (): void {
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
});
