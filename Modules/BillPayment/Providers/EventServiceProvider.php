<?php

namespace Modules\BillPayment\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\BillPayment\app\Events\BillPaymentFailed;
use Modules\BillPayment\app\Events\BillPaymentInitiated;
use Modules\BillPayment\app\Events\BillPaymentSuccessful;
use Modules\BillPayment\app\Listeners\FinalizeDebitOnSuccessListener;
use Modules\BillPayment\app\Listeners\ReleaseReservedOnFailureListener;
use Modules\BillPayment\app\Listeners\SendBillFailureNotificationListener;
use Modules\BillPayment\app\Listeners\SendBillReceiptListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        BillPaymentInitiated::class => [
            // Add LogBillInitiatedListener here when needed
        ],
        BillPaymentSuccessful::class => [
            FinalizeDebitOnSuccessListener::class,
            SendBillReceiptListener::class,
        ],
        BillPaymentFailed::class => [
            ReleaseReservedOnFailureListener::class,
            SendBillFailureNotificationListener::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;
    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
