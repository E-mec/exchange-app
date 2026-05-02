<?php

namespace Modules\BillPayment\app\Listeners;


use Modules\BillPayment\app\Events\BillPaymentFailed;
use Modules\Wallet\actions\ReleaseReservedFundsAction;

class ReleaseReservedOnFailureListener
{
    public function __construct(private readonly ReleaseReservedFundsAction $releaseReserved) {}

    public function handle(BillPaymentFailed $event): void
    {
        $this->releaseReserved->execute([
            'walletId'       => $event->billPayment->wallet_id,
            'userId'         => $event->billPayment->user_id,
            'reserveTxId'    => $event->billPayment->wallet_reserve_tx_id,
            'reference'      => $event->billPayment->reference,
            'idempotencyKey' => 'bill:release:' . $event->billPayment->reference,
        ]);
    }
}
