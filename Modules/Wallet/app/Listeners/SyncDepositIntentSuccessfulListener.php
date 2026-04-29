<?php

namespace Modules\Wallet\app\Listeners;

use App\Enums\StatusEnum;
use Modules\Payment\app\Events\PaymentSuccessful;
use Modules\Payment\Enums\PaymentTypeEnum;
use Modules\Wallet\Models\PaymentIntent;

class SyncDepositIntentSuccessfulListener
{
    public function handle(PaymentSuccessful $event): void
    {
        $payment = $event->payment;

        if ($payment->type !== PaymentTypeEnum::DEPOSIT) return;

        PaymentIntent::query()
            ->where('reference', $payment->reference)
            ->update([
                'status' => StatusEnum::SUCCESS,
                'payment_id' => $payment->id,
                'provider_reference' => $payment->provider_reference,
                'checkout_url' => null,
                'meta' => array_merge($payment->meta ?? [], [
                    'payment_id' => $payment->id,
                ]),
            ]);
    }
}
