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
            'walletId'       => $event->walletId,
            'userId'         => $event->userId,
            'reference'      => $event->reserveReference, // original reserve txn
            'idempotencyKey' => $event->idempotencyKey,
            'reason'         => 'payment_failed',
        ]);
    }
}
