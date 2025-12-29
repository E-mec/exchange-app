<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\app\Http\Controllers\DepositController;
use Modules\Wallet\app\Http\Controllers\WalletController;

Route::middleware('auth:api')->prefix('user')->group(function () {
    Route::post('/initiate/deposit', DepositController::class);
    Route::apiResource('wallets', WalletController::class)->only(['index', 'show']);

});
