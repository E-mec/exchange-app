<?php

use Illuminate\Support\Facades\Route;
use Modules\BillPayment\Http\Controllers\BillPaymentController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('billpayments', BillPaymentController::class)->names('billpayment');
});
