<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(config('fortify.middleware', ['web']))->group(function (): void {
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware([
            'guest:'.config('fortify.guard'),
            'throttle:'.config('fortify.limiters.login'),
        ])
        ->name('login.store');

    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware([
            'guest:'.config('fortify.guard'),
            'throttle:'.config('fortify.limiters.register'),
        ])
        ->name('register.store');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware([config('fortify.auth_middleware', 'auth').':'.config('fortify.guard')])
        ->name('logout');
});
