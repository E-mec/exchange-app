<?php

namespace Modules\Wallet\app\Observers;

use Modules\Wallet\Models\Withdrawal;

class WithdrawalObserver
{
    /**
     * Handle the Withdrawal "created" event.
     */
    public function created(Withdrawal $withdrawal): void {}

    /**
     * Handle the Withdrawal "creating" event.
     */
    public function creating(Withdrawal $withdrawal): void {}

    /**
     * Handle the Withdrawal "updated" event.
     */
    public function updated(Withdrawal $withdrawal): void {}

    /**
     * Handle the Withdrawal "deleted" event.
     */
    public function deleted(Withdrawal $withdrawal): void {}

    /**
     * Handle the Withdrawal "restored" event.
     */
    public function restored(Withdrawal $withdrawal): void {}

    /**
     * Handle the Withdrawal "force deleted" event.
     */
    public function forceDeleted(Withdrawal $withdrawal): void {}
}
