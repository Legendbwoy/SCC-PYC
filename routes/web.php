<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\ModuleOverviewController;
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
