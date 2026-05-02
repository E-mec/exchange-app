<?php

namespace Modules\BillPayment\app\Listeners;

use Modules\BillPayment\app\Events\BillPaymentSuccessful;
use Modules\Wallet\actions\FinalizeDebitAction;

class FinalizeDebitOnSuccessListener
{
    public function __construct(private readonly FinalizeDebitAction $finalizeDebit) {}

    public function handle(BillPaymentSuccessful $event): void
    {
        $this->finalizeDebit->execute([
            'walletId'       => $event->billPayment->wallet_id,
            'userId'         => $event->billPayment->user_id,
            'reserveTxId'    => $event->billPayment->wallet_reserve_tx_id,
            'reference'      => $event->billPayment->reference,
            'idempotencyKey' => 'bill:finalize:' . $event->billPayment->reference,
        ]);
    }
}
