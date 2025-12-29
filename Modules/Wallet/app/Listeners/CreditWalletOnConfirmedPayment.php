<?php

namespace Modules\Wallet\app\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Wallet\actions\CreditWalletAction;

final class CreditWalletOnConfirmedPayment
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected CreditWalletAction $creditWallet
    ) {}

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        $this->creditWallet->execute([
            'walletId'        => $event->walletId,
            'userId'          => $event->userId,
            'currency'        => $event->currency,
            'amount'          => $event->amount,
            'reference'       => $event->reference,
            'idempotencyKey'  => $event->idempotencyKey,
            'meta'            => $event->meta,
        ]);
    }
}
