<?php

namespace Modules\Payment\app\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Payment\app\Events\PaymentInitialized;

class LogPaymentInitializedListener
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(PaymentInitialized $event): void
    {
        logger()->info('Payment initialized', [
            'payment_id' => $event->payment->id,
            'user_id'    => $event->payment->user_id,
            'provider'   => $event->payment->provider,
            'amount'     => $event->payment->amount,
            'currency'   => $event->payment->currency,
        ]);
    }}
