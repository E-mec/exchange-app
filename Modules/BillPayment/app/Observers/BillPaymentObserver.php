<?php

namespace Modules\BillPayment\app\Observers;

use Modules\BillPayment\Models\BillPayment;

class BillPaymentObserver
{
    /**
     * Handle the BillPayment "created" event.
     */
    public function created(BillPayment $billPayment): void {}

    /**
     * Handle the BillPayment "creating" event.
     */
    public function creating(BillPayment $billPayment): void {}

    /**
     * Handle the BillPayment "updated" event.
     */
    public function updated(BillPayment $billPayment): void {}

    /**
     * Handle the BillPayment "deleted" event.
     */
    public function deleted(BillPayment $billPayment): void {}

    /**
     * Handle the BillPayment "restored" event.
     */
    public function restored(BillPayment $billPayment): void {}

    /**
     * Handle the BillPayment "force deleted" event.
     */
    public function forceDeleted(BillPayment $billPayment): void {}
}
