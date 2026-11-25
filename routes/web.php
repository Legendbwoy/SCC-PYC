<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\MediaGalleryController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\MemberInvitationController;
use App\Http\Controllers\Portal\AccountingPortalController;
use App\Http\Controllers\Portal\CommunicationsController;
use App\Http\Controllers\Portal\ContributionsController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\EventsPortalController;
use App\Http\Controllers\Portal\ExpensesPortalController;
use App\Http\Controllers\Portal\GoogleSyncController;
use App\Http\Controllers\Portal\MemberSelfServiceController;
use App\Http\Controllers\Portal\MembershipController;
use App\Http\Controllers\Portal\ModuleOverviewController;
use App\Http\Controllers\Portal\NotificationPreferenceController;
use App\Http\Controllers\Portal\PaymentsPortalController;
use App\Http\Controllers\Portal\ReportsPortalController;
use App\Http\Controllers\Portal\SecurityProfileController;
use App\Modules\Events\Models\PycEvent;
use App\Support\Media\Models\MediaAsset;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', static function () {
    $upcomingEvents = Schema::hasTable('events')
        ? PycEvent::query()
            ->where('status', '!=', 'cancelled')
            ->where('starts_at', '>=', now()->subDay())
            ->orderBy('starts_at')
            ->limit(3)
            ->get()
        : collect();

    $publicGalleryPhotos = Schema::hasTable('media_assets')
        ? MediaAsset::query()
            ->with('event')
            ->where('is_public', true)
            ->orderByDesc('is_featured')
            ->latest('id')
            ->limit(6)
            ->get()
        : collect();

    return view('welcome', [
        'upcomingEvents' => $upcomingEvents,
        'publicGalleryPhotos' => $publicGalleryPhotos,
    ]);
})->name('home');

Route::get('/invitations/{token}', [MemberInvitationController::class, 'show'])
    ->name('invitations.show');
Route::post('/invitations/{token}', [MemberInvitationController::class, 'store'])
    ->middleware('throttle:login')
    ->name('invitations.accept');

Route::middleware(['auth', 'pyc.active'])->group(function (): void {
    Route::get('/profile/security', [SecurityProfileController::class, 'show'])
        ->name('profile.security');
    Route::post('/profile/avatar', [SecurityProfileController::class, 'updateAvatar'])
        ->name('profile.avatar.update');
    Route::delete('/profile/avatar', [SecurityProfileController::class, 'removeAvatar'])
        ->name('profile.avatar.destroy');

    Route::middleware(['pyc.2fa'])->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/profile/notifications', [NotificationPreferenceController::class, 'show'])
            ->name('profile.notifications');
        Route::patch('/profile/notifications', [NotificationPreferenceController::class, 'update'])
            ->name('profile.notifications.update');

        // Member Portal Self-Service Routes (Sprint 3, 5 & 7)
        Route::prefix('my')->name('member.')->group(function (): void {
            Route::get('/profile', [MemberSelfServiceController::class, 'profile'])->name('profile');
            Route::patch('/profile', [MemberSelfServiceController::class, 'updateProfile'])->name('profile.update');
            Route::get('/dues', [MemberSelfServiceController::class, 'dues'])->name('dues');
            Route::get('/payments', [MemberSelfServiceController::class, 'payments'])->name('payments');
            Route::post('/payments/initialize', [MemberSelfServiceController::class, 'initializePayment'])->name('payments.initialize');
            Route::get('/payments/callback', [MemberSelfServiceController::class, 'paymentCallback'])->name('payments.callback');
            Route::get('/events', [MemberSelfServiceController::class, 'events'])->name('events');
            Route::post('/events/{event}/register', [MemberSelfServiceController::class, 'registerEvent'])->name('events.register');
            Route::post('/events/{event}/cancel', [MemberSelfServiceController::class, 'cancelEventRegistration'])->name('events.cancel');
        });

        // Membership & Google Forms Integration (Sprint 2)
        Route::prefix('membership')->name('membership.')->group(function (): void {
            Route::get('/', [MembershipController::class, 'index'])->name('index');
            Route::post('/', [MembershipController::class, 'store'])->name('store');

            Route::get('/google-sync', [GoogleSyncController::class, 'index'])->name('google-sync');
            Route::put('/google-sync/connection', [GoogleSyncController::class, 'updateConnection'])
                ->name('google-sync.connection');
            Route::post('/google-sync/run', [GoogleSyncController::class, 'runSync'])
                ->name('google-sync.run');
            Route::post('/google-sync/{syncLog}/retry', [GoogleSyncController::class, 'retrySync'])
                ->name('google-sync.retry');

            Route::get('/{member}', [MembershipController::class, 'show'])->name('show');
            Route::patch('/{member}', [MembershipController::class, 'update'])->name('update');
            Route::post('/{member}/approve', [MembershipController::class, 'approve'])->name('approve');
            Route::post('/{member}/reject', [MembershipController::class, 'reject'])->name('reject');
            Route::post('/{member}/archive', [MembershipController::class, 'archive'])->name('archive');
            Route::post('/{member}/invite', [MembershipController::class, 'invite'])->name('invite');
            Route::post('/{member}/photo', [MembershipController::class, 'updatePhoto'])->name('photo');
        });

        // Contributions, Monthly Dues & Obligations (Sprint 4)
        Route::prefix('contributions')->name('contributions.')->group(function (): void {
            Route::get('/', [ContributionsController::class, 'index'])->name('index');
            Route::get('/obligations', [ContributionsController::class, 'obligations'])->name('obligations');
            Route::post('/dues/generate', [ContributionsController::class, 'generateDues'])->name('dues.generate');
            Route::post('/campaigns', [ContributionsController::class, 'storeCampaign'])->name('campaigns.store');
            Route::post('/obligations/{obligation}/adjust', [ContributionsController::class, 'adjustObligation'])
                ->name('obligations.adjust');
        });

        // Payments, Maker-Checker Cash Approvals & Official PDF Receipts (Sprint 5)
        Route::prefix('payments')->name('payments.')->group(function (): void {
            Route::get('/', [PaymentsPortalController::class, 'index'])->name('index');
            Route::post('/cash', [PaymentsPortalController::class, 'recordCash'])->name('cash.store');
            Route::post('/settlements', [PaymentsPortalController::class, 'storeSettlement'])->name('settlements.store');
            Route::post('/{payment}/approve', [PaymentsPortalController::class, 'approveCash'])->name('approve');
            Route::post('/{payment}/reject', [PaymentsPortalController::class, 'rejectCash'])->name('reject');
            Route::get('/{payment}/receipt/pdf', [PaymentsPortalController::class, 'downloadReceiptPdf'])->name('receipt.pdf');
        });

        // Double-Entry Accounting & Period Controls (Sprint 6)
        Route::prefix('accounting')->name('accounting.')->group(function (): void {
            Route::get('/', [AccountingPortalController::class, 'index'])->name('index');
            Route::post('/journals/{journal}/reverse', [AccountingPortalController::class, 'reverseJournal'])
                ->name('journals.reverse');
            Route::post('/periods/{period}/close', [AccountingPortalController::class, 'closePeriod'])
                ->name('periods.close');
        });

        // Expense Management & Dual Authorization (Sprint 6)
        Route::prefix('expenses')->name('expenses.')->group(function (): void {
            Route::get('/', [ExpensesPortalController::class, 'index'])->name('index');
            Route::post('/', [ExpensesPortalController::class, 'store'])->name('store');
            Route::post('/{expense}/approve', [ExpensesPortalController::class, 'approve'])->name('approve');
            Route::post('/{expense}/reject', [ExpensesPortalController::class, 'reject'])->name('reject');
            Route::post('/{expense}/pay', [ExpensesPortalController::class, 'pay'])->name('pay');
        });

        // Parish Youth Events & Registrations (Sprint 7)
        Route::prefix('events')->name('events.')->group(function (): void {
            Route::get('/', [EventsPortalController::class, 'index'])->name('index');
            Route::post('/', [EventsPortalController::class, 'store'])->name('store');
            Route::get('/{event}', [EventsPortalController::class, 'show'])->name('show');
            Route::post('/{event}/banner', [EventsPortalController::class, 'updateBanner'])->name('banner.update');
            Route::delete('/{event}/banner', [EventsPortalController::class, 'removeBanner'])->name('banner.destroy');
            Route::post('/{event}/announce', [EventsPortalController::class, 'announce'])->name('announce');
            Route::post('/{event}/remind', [EventsPortalController::class, 'remind'])->name('remind');
            Route::post('/{event}/cancel', [EventsPortalController::class, 'cancel'])->name('cancel');
            Route::post('/registrations/{registration}/attendance', [EventsPortalController::class, 'markAttendance'])
                ->name('attendance');
        });

        // Executive & Auditor Reports & PDF/CSV Exports (Sprint 7)
        Route::prefix('reports')->name('reports.')->group(function (): void {
            Route::get('/', [ReportsPortalController::class, 'index'])->name('index');
            Route::get('/export/{format}', [ReportsPortalController::class, 'export'])
                ->whereIn('format', ['pdf', 'csv'])
                ->name('export');
        });

        // Communications Hub (Milestone N1)
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

            Route::get('/media', [MediaGalleryController::class, 'index'])->name('media.index');
            Route::post('/media/gallery', [MediaGalleryController::class, 'storeGallery'])->name('media.gallery.store');
            Route::patch('/media/gallery/{mediaAsset}', [MediaGalleryController::class, 'updateGallery'])->name('media.gallery.update');
            Route::delete('/media/gallery/{mediaAsset}', [MediaGalleryController::class, 'destroyGallery'])->name('media.gallery.destroy');
            Route::post('/media/branding/{slot}', [MediaGalleryController::class, 'updateBrandingImage'])->name('media.branding.update');
            Route::delete('/media/branding/{slot}', [MediaGalleryController::class, 'removeBrandingImage'])->name('media.branding.destroy');

            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        });
    });
});
