<?php

namespace Modules\Wallet\app\Observers;

use Modules\Wallet\Models\WalletTransaction;

class WalletTransactionObserver
{
    /**
     * Handle the WalletTransaction "created" event.
     */
    public function created(WalletTransaction $walletTransaction): void {}

    /**
     * Handle the WalletTransaction "creating" event.
     */
    public function creating(WalletTransaction $walletTransaction): void {}

    /**
     * Handle the WalletTransaction "updated" event.
     */
    public function updated(WalletTransaction $walletTransaction): void {}

    /**
     * Handle the WalletTransaction "deleted" event.
     */
    public function deleted(WalletTransaction $walletTransaction): void {}

    /**
     * Handle the WalletTransaction "restored" event.
     */
    public function restored(WalletTransaction $walletTransaction): void {}

    /**
     * Handle the WalletTransaction "force deleted" event.
     */
    public function forceDeleted(WalletTransaction $walletTransaction): void {}
}
