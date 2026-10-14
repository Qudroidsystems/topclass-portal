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