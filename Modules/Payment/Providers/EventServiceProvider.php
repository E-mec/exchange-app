<?php

namespace Modules\Payment\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Payment\app\Events\PaymentFailed;
use Modules\Payment\app\Events\PaymentSuccessful;
use Modules\Payment\app\Listeners\InitializePaymentListener;
use Modules\Wallet\app\Events\DepositInitiatedEvent;
use Modules\Wallet\app\Listeners\CreditWalletOnConfirmedPayment;
use Modules\Wallet\app\Listeners\FinalizeDebitListener;
use Modules\Wallet\app\Listeners\ReleaseReservedFundsListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [

        /** ✅ PAYMENT SUCCESS */
        PaymentSuccessful::class => [
            CreditWalletOnConfirmedPayment::class,
            FinalizeDebitListener::class,
        ],

        /** ❌ PAYMENT FAILED */
        PaymentFailed::class => [
            ReleaseReservedFundsListener::class,
        ],

        DepositInitiatedEvent::class => [
            InitializePaymentListener::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
