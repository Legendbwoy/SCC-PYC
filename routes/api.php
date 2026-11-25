<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PYC API v1 Routes (Spec Section 13.1: Base path /api/v1)
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
            'version' => '1.2.0-foundation',
        ],
    ]))->name('api.v1.status');
});
