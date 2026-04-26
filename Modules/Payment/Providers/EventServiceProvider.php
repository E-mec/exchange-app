<?php

namespace Modules\Payment\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Payment\app\Events\PaymentFailed;
use Modules\Payment\app\Events\PaymentInitialized;
use Modules\Payment\app\Events\PaymentSuccessful;
use Modules\Payment\app\Listeners\InitializePaymentListener;
use Modules\Wallet\app\Events\DepositInitiatedEvent;
use Modules\Wallet\app\Listeners\CreditWalletOnConfirmedPayment;
use Modules\Wallet\app\Listeners\FinalizeDebitListener;
use Modules\Wallet\app\Listeners\ReleaseReservedFundsListener;
use Modules\Wallet\app\Listeners\SyncDepositIntentFailedListener;
use Modules\Wallet\app\Listeners\SyncDepositIntentInitializedListener;
use Modules\Wallet\app\Listeners\SyncDepositIntentSuccessfulListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        PaymentInitialized::class => [
            SyncDepositIntentInitializedListener::class,
        ],

        PaymentSuccessful::class => [
            SyncDepositIntentSuccessfulListener::class,
            CreditWalletOnConfirmedPayment::class,
//            FinalizeDebitListener::class,
        ],

        PaymentFailed::class => [
            SyncDepositIntentFailedListener::class,
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
