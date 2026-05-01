<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\app\Http\Controllers\AuthController;
use Modules\Auth\app\Http\Controllers\LoginController;
use Modules\Auth\app\Http\Controllers\ProfileController;
use Modules\Auth\app\Http\Controllers\ResendOtpController;
use Modules\Auth\app\Http\Controllers\PasswordResetController;

Route::prefix('auth')->group(function () {
   Route::post('register', [AuthController::class, 'register']);
    Route::post('/verify/otp', [AuthController::class, 'verify'])
        ->middleware('throttle:2,1');

    Route::post('/login', [LoginController::class, 'login']);

    Route::post('/logout', [LoginController::class, 'logout']);

    Route::post('/password/otp', [PasswordResetController::class, 'requestOtp']);
    Route::post('/password/otp/verify', [PasswordResetController::class, 'verifyOtp']);
    Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);

    Route::middleware(['auth:api'])->group(function () {
        Route::get('/fetch/profile', [LoginController::class, 'me']);
        Route::get('/refresh', [LoginController::class, 'refresh']);
    });

});

Route::post('resend/otp', ResendOtpController::class)->middleware('throttle:2,1');

Route::middleware(['auth:api'])->group(function () {

    Route::post('/upload/profile/picture', [ProfileController::class, 'saveProfilePicture']);

    Route::patch('/update/profile', [ProfileController::class, 'update']);

});
