<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AccountingApiController;
use App\Http\Controllers\Api\V1\ContributionsApiController;
use App\Http\Controllers\Api\V1\MembershipApiController;
use App\Http\Controllers\Api\V1\NotificationApiController;
use App\Http\Controllers\Api\V1\NotificationWebhookController;
use App\Http\Controllers\Api\V1\PaymentsApiController;
use App\Http\Controllers\Api\V1\PaystackWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PYC API v1 Routes (Spec Section 13.1–13.7)
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
            'version' => '1.2.0-sprint6',
        ],
    ]))->name('api.v1.status');

    // Section 13.7 Paystack HMAC-SHA512 signed webhook
    Route::post('/webhooks/paystack', PaystackWebhookController::class)
        ->name('api.v1.webhooks.paystack');

    // Authenticated Notification Delivery Webhooks
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

        // Section 13.3 Contribution & Dues endpoints
        Route::get('/contributions/types', [ContributionsApiController::class, 'types']);
        Route::get('/contributions/campaigns', [ContributionsApiController::class, 'campaigns']);
        Route::post('/contributions/campaigns', [ContributionsApiController::class, 'storeCampaign']);
        Route::patch('/contributions/campaigns/{campaign}', [ContributionsApiController::class, 'updateCampaign']);
        Route::post('/dues/generate', [ContributionsApiController::class, 'generateDues']);
        Route::get('/dues/member/{member}', [ContributionsApiController::class, 'memberDues']);
        Route::get('/dues/outstanding', [ContributionsApiController::class, 'outstanding']);
        Route::post('/dues/{obligation}/adjustments', [ContributionsApiController::class, 'storeAdjustment']);

        // Section 13.4 Payment & Receipt endpoints
        Route::get('/payments', [PaymentsApiController::class, 'index']);
        Route::post('/payments/initialize', [PaymentsApiController::class, 'initialize']);
        Route::post('/payments/verify', [PaymentsApiController::class, 'verify']);
        Route::post('/payments/cash', [PaymentsApiController::class, 'recordCash']);
        Route::post('/payments/{payment}/approve', [PaymentsApiController::class, 'approve']);
        Route::post('/payments/{payment}/reject', [PaymentsApiController::class, 'reject']);
        Route::get('/payments/{payment}/receipt', [PaymentsApiController::class, 'receipt']);
        Route::post('/payments/{payment}/refunds', [PaymentsApiController::class, 'refund']);
        Route::get('/payments/{reference}', [PaymentsApiController::class, 'showByReference']);

        // Section 13.5 Accounting & Expense endpoints
        Route::get('/accounting/accounts', [AccountingApiController::class, 'accounts']);
        Route::get('/accounting/journals', [AccountingApiController::class, 'journals']);
        Route::get('/accounting/journals/{journal}', [AccountingApiController::class, 'showJournal']);
        Route::post('/accounting/journals/post', [AccountingApiController::class, 'postManualJournal']);
        Route::post('/accounting/journals/{journal}/reverse', [AccountingApiController::class, 'reverseJournal']);
        Route::get('/accounting/trial-balance', [AccountingApiController::class, 'trialBalance']);
        Route::get('/accounting/income-expenditure', [AccountingApiController::class, 'incomeExpenditure']);
        Route::get('/accounting/balance-sheet', [AccountingApiController::class, 'balanceSheet']);
        Route::post('/accounting/periods/{period}/close', [AccountingApiController::class, 'closePeriod']);

        Route::get('/expenses', [AccountingApiController::class, 'expenses']);
        Route::post('/expenses', [AccountingApiController::class, 'storeExpense']);
        Route::post('/expenses/{expense}/submit', [AccountingApiController::class, 'submitExpense']);
        Route::post('/expenses/{expense}/approve', [AccountingApiController::class, 'approveExpense']);
        Route::post('/expenses/{expense}/reject', [AccountingApiController::class, 'rejectExpense']);
        Route::post('/expenses/{expense}/pay', [AccountingApiController::class, 'payExpense']);

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
