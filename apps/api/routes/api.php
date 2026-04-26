<?php

use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Onboarding\AthleteOnboardingController;
use App\Http\Controllers\Api\V1\Profile\AthleteProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class)->name('api.v1.health');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', MeController::class)->name('api.v1.me');
        Route::get('/me/onboarding', [AthleteOnboardingController::class, 'show'])->name('api.v1.me.onboarding.show');
        Route::patch('/me/onboarding', [AthleteOnboardingController::class, 'update'])->name('api.v1.me.onboarding.update');
        Route::get('/me/profile', [AthleteProfileController::class, 'show'])->name('api.v1.me.profile.show');
        Route::patch('/me/profile', [AthleteProfileController::class, 'update'])->name('api.v1.me.profile.update');
    });
});
