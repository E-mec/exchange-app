<?php

namespace Modules\Wallet\app\Observers;

use Modules\Wallet\Models\PaymentIntent;

class PaymentIntentObserver
{
    /**
     * Handle the PaymentIntent "created" event.
     */
    public function created(PaymentIntent $paymentIntent): void {}

    /**
     * Handle the PaymentIntent "creating" event.
     */
    public function creating(PaymentIntent $paymentIntent): void {}

    /**
     * Handle the PaymentIntent "updated" event.
     */
    public function updated(PaymentIntent $paymentIntent): void {}

    /**
     * Handle the PaymentIntent "deleted" event.
     */
    public function deleted(PaymentIntent $paymentIntent): void {}

    /**
     * Handle the PaymentIntent "restored" event.
     */
    public function restored(PaymentIntent $paymentIntent): void {}

    /**
     * Handle the PaymentIntent "force deleted" event.
     */
    public function forceDeleted(PaymentIntent $paymentIntent): void {}
}
