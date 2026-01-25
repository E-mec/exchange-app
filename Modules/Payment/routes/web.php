<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\app\Http\Controllers\PaymentController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('payments', PaymentController::class)->names('payment');
});
