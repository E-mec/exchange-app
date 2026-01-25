<?php

namespace Modules\Wallet\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Auth\app\Events\UserRegisteredEvent;
use Modules\Payment\app\Events\PaymentSuccessful;
use Modules\Wallet\app\Listeners\CreateWalletForUser;

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

        ]
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
