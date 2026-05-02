<?php

use Illuminate\Support\Facades\Route;
use Modules\BillPayment\app\Http\Controllers\BillPaymentController;

Route::middleware(['auth:api'])->prefix('bills')->group(function () {
    Route::post('/purchase',    [BillPaymentController::class, 'initiate']);
    Route::get('/{id}/status', [BillPaymentController::class, 'status']);
    Route::get('/history',      [BillPaymentController::class, 'history']);
});

// Webhook from VTU providers (no auth middleware, uses signature verification)
//Route::post('/bill-webhooks/{provider}', BillWebhookController::class)
//    ->middleware(VerifyBillWebhookSignature::class);
