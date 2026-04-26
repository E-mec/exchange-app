<?php

namespace Modules\Wallet\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Auth\app\Events\UserRegisteredEvent;
use Modules\Payment\app\Events\PaymentSuccessful;
use Modules\Wallet\app\Events\DepositInitiatedEvent;
use Modules\Wallet\app\Events\WithdrawalFailed;
use Modules\Wallet\app\Events\WithdrawalSucceeded;
use Modules\Wallet\app\Listeners\CreateWalletForUser;
use Modules\Wallet\app\Listeners\FinalizeWithdrawalListener;
use Modules\Wallet\app\Listeners\ReleaseWithdrawalFundsListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        UserRegisteredEvent::class => [
            CreateWalletForUser::class
        ],
        PaymentSuccessful::class => [

        ],

        DepositInitiatedEvent::class => [
            // your payment gateway dispatch listener goes here
            // e.g. InitiatePaystackPaymentListener::class
        ],
        WithdrawalSucceeded::class => [
            FinalizeWithdrawalListener::class,
        ],
        WithdrawalFailed::class => [
            ReleaseWithdrawalFundsListener::class,
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
