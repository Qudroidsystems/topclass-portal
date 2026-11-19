<?php

use Illuminate\Support\Facades\Route;

/*
| API routes (prefixed /api). Currently only the feature-flag endpoints used by the remote control portal.
*/

// =========================================================================
// Mobile app API v1 (Sanctum tokens) — see routes/api_v1.php
// =========================================================================
Route::prefix('v1')->name('api.v1.')->group(base_path('routes/api_v1.php'));

// =========================================================================
// Module feature flags — the remote control portal reads/sets 1/0 here.
// Protected by the shared key (bearer) + HMAC signature on writes.
// =========================================================================
Route::middleware('remote.portal')->prefix('feature-flags')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\FeatureFlagApiController::class, 'index']);
    Route::get('/health', [\App\Http\Controllers\Api\FeatureFlagApiController::class, 'health']);
    Route::get('/catalog', [\App\Http\Controllers\Api\FeatureFlagApiController::class, 'catalog']);
    Route::post('/sync', [\App\Http\Controllers\Api\FeatureFlagApiController::class, 'sync']);
});

// =========================================================================
// Offline CBT desktop app (device key auth) — /api/offline-cbt/v1/...
// =========================================================================
Route::prefix('offline-cbt/v1')
    ->middleware([\App\Http\Middleware\OfflineCbtDevice::class, 'throttle:120,1'])
    ->group(function () {
        Route::get('ping',            [\App\Http\Controllers\OfflineCbt\OfflineCbtApiController::class, 'ping']);
        Route::get('exams',           [\App\Http\Controllers\OfflineCbt\OfflineCbtApiController::class, 'exams']);
        Route::post('package',        [\App\Http\Controllers\OfflineCbt\OfflineCbtApiController::class, 'package']);
        Route::post('attempts',       [\App\Http\Controllers\OfflineCbt\OfflineCbtApiController::class, 'upload']);
        Route::post('attempts/status',[\App\Http\Controllers\OfflineCbt\OfflineCbtApiController::class, 'status']);
    });
