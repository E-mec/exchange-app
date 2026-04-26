<?php

namespace Modules\Payment\app\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Auth\Models\User;
use Modules\Payment\actions\InitializePayment;
use Modules\Wallet\app\Events\DepositInitiatedEvent;

class InitializePaymentListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected InitializePayment $initializePayment
    ) {}

    /**
     * Handle the event.
     */
    public function handle(DepositInitiatedEvent $event): void {
        $this->initializePayment->execute(
            [
            'user_id'   => $event->userId,
            'reference' => $event->reference, // IMPORTANT: reuse wallet reference
            'amount'    => $event->amount,
            'currency'  => $event->currency,
            'provider'  => $event->provider,
            'email'     => User::find($event->userId)?->email, // or fetch via user repo
            'meta'      => [
                'source' => 'wallet_deposit',
            ],
        ],
            auth()->id()
        );
    }
}
