<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\app\Http\Controllers\DepositController;
use Modules\Wallet\app\Http\Controllers\WalletController;
use Modules\Wallet\app\Http\Controllers\WalletTransferController;
use Modules\Wallet\app\Http\Controllers\WithdrawalController;

Route::middleware('auth:api')->prefix('user')->group(function () {
    Route::post('/initiate/deposit', DepositController::class);
    Route::apiResource('wallets', WalletController::class)->only(['index', 'show']);
    Route::post('/transfer/funds', WalletTransferController::class);

    Route::post('/withdrawals', [WithdrawalController::class, 'store']);

    Route::get('/transactions', [WalletController::class, 'transactions']);
    Route::get('/withdrawals', [WithdrawalController::class, 'index']);


});
