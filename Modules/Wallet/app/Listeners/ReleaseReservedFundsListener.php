<?php

namespace Modules\Wallet\app\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Wallet\actions\ReleaseReservedFundsAction;

final class ReleaseReservedFundsListener
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle($event): void {
        app(ReleaseReservedFundsAction::class)->execute([
//            'walletId'       => $event->payment->walletId,
//            'userId'         => $event->payment->userId,
            'reference'      => $event->payment->reserveReference, // original reserve txn
            'idempotencyKey' => $event->payment->idempotencyKey,
            'reason'         => 'payment_failed',
        ]);
    }
}
