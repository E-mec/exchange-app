<?php
use Illuminate\Support\Facades\Route;
use Modules\Payment\app\Http\Controllers\PaymentController;
use Modules\Payment\app\Http\Controllers\WebhookController;
use Modules\Payment\app\Http\Middleware\VerifyPaymentWebhook;

Route::middleware(['auth:api'])
    ->prefix('payments')
    ->group(function () {
        Route::post('initialize', [PaymentController::class, 'initialize']);
    });

Route::post('/webhooks/{provider}',WebhookController::class)->middleware([VerifyPaymentWebhook::class]);

