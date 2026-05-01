<?php

use Illuminate\Support\Facades\Route;
use Modules\BillPayment\Http\Controllers\BillPaymentController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('billpayments', BillPaymentController::class)->names('billpayment');
});
