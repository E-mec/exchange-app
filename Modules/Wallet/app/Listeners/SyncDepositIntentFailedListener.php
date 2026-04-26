<?php

namespace Modules\Wallet\app\Listeners;

use App\Enums\StatusEnum;
use Modules\Payment\app\Events\PaymentFailed;
use Modules\Wallet\Models\PaymentIntent;

class SyncDepositIntentFailedListener
{
    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;

        PaymentIntent::query()
            ->where('reference', $payment->reference)
            ->update([
                'status' => StatusEnum::FAILED,
                'payment_id' => $payment->id,
                'provider_reference' => $payment->provider_reference,
                'checkout_url' => null,
                'meta' => array_merge($payment->meta ?? [], [
                    'payment_id' => $payment->id,
                ]),
            ]);
    }
}
