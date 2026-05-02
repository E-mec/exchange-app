<?php

use Illuminate\Support\Facades\Route;
use Modules\BillPayment\app\Http\Controllers\BillPaymentController;
use Modules\BillPayment\app\Http\Controllers\BillServiceController;

Route::middleware(['auth:api'])
    ->prefix('bills')
    ->group(function () {

        // Services catalogue
        Route::get('/services',                      [BillServiceController::class, 'index']);
        Route::get('/services/{slug}',               [BillServiceController::class, 'show']);
        Route::get('/services/{slug}/variations',    [BillServiceController::class, 'variations']);

        // Bill payment transactions
        Route::post('/purchase',                     [BillPaymentController::class, 'initiate']);
        Route::get('/{id}/status',                   [BillPaymentController::class, 'status']);
        Route::get('/history',                       [BillPaymentController::class, 'history']);
    });

// Webhook from VTU providers (no auth middleware, uses signature verification)
//Route::post('/bill-webhooks/{provider}', BillWebhookController::class)
//    ->middleware(VerifyBillWebhookSignature::class);
