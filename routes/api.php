<?php

use Illuminate\Support\Facades\Route;

/*
| API routes (prefixed /api). Currently only the feature-flag endpoints used by the remote control portal.
*/

// Module feature flags — the remote control portal reads/sets 1/0 here.
// Protected by the shared key (bearer) + HMAC signature on writes.
// =========================================================================
Route::middleware('remote.portal')->prefix('feature-flags')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\FeatureFlagApiController::class, 'index']);
    Route::get('/health', [\App\Http\Controllers\Api\FeatureFlagApiController::class, 'health']);
    Route::get('/catalog', [\App\Http\Controllers\Api\FeatureFlagApiController::class, 'catalog']);
    Route::post('/sync', [\App\Http\Controllers\Api\FeatureFlagApiController::class, 'sync']);
});