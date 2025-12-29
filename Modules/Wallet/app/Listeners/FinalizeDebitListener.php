<?php

namespace Modules\Wallet\app\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Wallet\actions\FinalizeDebitAction;

final class FinalizeDebitListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected FinalizeDebitAction $finalizeDebit
    ) {}

    public function handle( $event): void
    {
        $this->finalizeDebit->execute([
            'reference'       => $event->reference,
            'walletId'        => $event->walletId,
            'currency'        => $event->currency,
            'amount'          => $event->amount,
            'idempotencyKey'  => $event->idempotencyKey,
            'meta'            => $event->meta,
        ]);
    }
}
