<?php

namespace Modules\Wallet\app\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Wallet\actions\CreateWalletAction;
use Modules\Wallet\enums\CurrencyEnum;

class CreateWalletForUser
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle($event): void {

        $user = $event->user;

        foreach(CurrencyEnum::cases() as $currency) {
            app(CreateWalletAction::class)->execute($user, $currency);
        }
    }
}
