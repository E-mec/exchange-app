<?php

namespace Modules\Wallet\app\Listeners;

use App\Enums\StatusEnum;
use Modules\Payment\app\Events\PaymentInitialized;
use Modules\Wallet\Models\PaymentIntent;

class SyncDepositIntentInitializedListener
{
    public function handle(PaymentInitialized $event): void
    {
        $payment = $event->payment;

        PaymentIntent::query()
            ->where('reference', $payment->reference)
            ->update([
                'status' => StatusEnum::PROCESSING,
                'payment_id' => $payment->id,
                'provider_reference' => $payment->provider_reference,
                'checkout_url' => $payment->meta['checkout_url'] ?? null,
                'meta' => array_merge($payment->meta ?? [], [
                    'payment_id' => $payment->id,
                ]),
            ]);
    }
}
