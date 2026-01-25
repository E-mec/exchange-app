<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\app\Http\Controllers\WalletController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('wallets', WalletController::class)->names('wallet');
});
