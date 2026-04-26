<?php

namespace Modules\Wallet\app\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Wallet\actions\CreditWalletAction;
use Modules\Wallet\Models\Wallet;

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
        $payment = $event->payment;

        $wallet  = Wallet::where('user_id', $payment->user_id)
            ->where('currency', $payment->currency)
            ->firstOrFail();

//        $this->creditWallet->execute([
//            'walletId'        => $event->walletId,
//            'userId'          => $event->userId,
//            'currency'        => $event->currency,
//            'amount'          => $event->amount,
//            'reference'       => $event->reference,
//            'idempotencyKey'  => $event->idempotencyKey,
//            'meta'            => $event->meta,
//        ]);

        $this->creditWallet->execute([
            'walletId'       => $wallet->id,
            'userId'         => $payment->user_id,
            'currency'       => $payment->currency,
            'amount'         => $payment->amount,
            'reference'      => $payment->reference,
            'idempotencyKey' => 'credit:' . $payment->reference,
            'meta'           => ['payment_id' => $payment->id],
        ]);
    }
}
