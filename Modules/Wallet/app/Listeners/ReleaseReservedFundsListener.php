<?php

namespace Modules\Wallet\app\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Wallet\actions\ReleaseReservedFundsAction;
use Modules\Wallet\enums\TransactionTypeEnum;
use Modules\Wallet\Models\WalletTransaction;

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
//        app(ReleaseReservedFundsAction::class)->execute([
////            'walletId'       => $event->payment->walletId,
////            'userId'         => $event->payment->userId,
//            'reference'      => $event->payment->reserveReference, // original reserve txn
//            'idempotencyKey' => $event->payment->idempotencyKey,
//            'reason'         => 'payment_failed',
//        ]);

        $payment = $event->payment;

        // Only release if there's a reserve transaction to release
        $reserveTxn = WalletTransaction::where('reference', $payment->reference)
            ->where('type', TransactionTypeEnum::RESERVE)
            ->first();

        if (! $reserveTxn) return; // deposit failure — nothing to release

        app(ReleaseReservedFundsAction::class)->execute([
            'reference'      => $payment->reference,
            'idempotencyKey' => $payment->reference . ':release',
            'reason'         => 'payment_failed',
        ]);

    }
}
