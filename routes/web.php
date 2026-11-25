<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Portal\CommunicationsController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\ModuleOverviewController;
use App\Http\Controllers\Portal\NotificationPreferenceController;
use App\Http\Controllers\Portal\SecurityProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', static function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'pyc.active'])->group(function (): void {
    // Account & 2FA Security setup route (accessible before 2FA confirmation so officers can enroll)
    Route::get('/profile/security', [SecurityProfileController::class, 'show'])
        ->name('profile.security');

    // Protected portal routes requiring confirmed 2FA for designated administrative/executive roles
    Route::middleware(['pyc.2fa'])->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/profile/notifications', [NotificationPreferenceController::class, 'show'])
            ->name('profile.notifications');
        Route::patch('/profile/notifications', [NotificationPreferenceController::class, 'update'])
            ->name('profile.notifications.update');

        Route::prefix('communications')->name('communications.')->group(function (): void {
            Route::get('/', [CommunicationsController::class, 'index'])->name('index');
            Route::get('/templates', [CommunicationsController::class, 'templates'])->name('templates');
            Route::post('/templates', [CommunicationsController::class, 'storeTemplate'])->name('templates.store');
            Route::post('/test', [CommunicationsController::class, 'sendTest'])
                ->middleware('throttle:notifications-test')
                ->name('test');
            Route::get('/deliveries', [CommunicationsController::class, 'deliveries'])->name('deliveries');
            Route::post('/deliveries/{delivery}/retry', [CommunicationsController::class, 'retryDelivery'])
                ->name('deliveries.retry');
            Route::get('/campaigns', [CommunicationsController::class, 'campaigns'])->name('campaigns');
            Route::post('/campaigns', [CommunicationsController::class, 'storeCampaign'])
                ->middleware('throttle:notifications-bulk')
                ->name('campaigns.store');
            Route::post('/campaigns/{campaign}/cancel', [CommunicationsController::class, 'cancelCampaign'])
                ->name('campaigns.cancel');
        });

        Route::get('/modules/{module}', ModuleOverviewController::class)
            ->whereAlpha('module')
            ->name('modules.overview');

        Route::prefix('admin')->name('admin.')->group(function (): void {
            Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
            Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
            Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');

            Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings.index');
            Route::put('/settings', [SystemSettingController::class, 'update'])->name('settings.update');

            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        });
    });
});
