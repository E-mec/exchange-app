<?php

namespace Modules\Wallet\actions;

use App\Enums\StatusEnum;
use Illuminate\Support\Str;
use Modules\Wallet\app\Events\DepositInitiatedEvent;
use Modules\Wallet\Models\PaymentIntent;

class InitiateDepositAction
{
    public function handle($amount, $currency, $channel)
    {
        $user = auth()->user();

        $reference = 'DEP-' . strtoupper(Str::random(12));

        // Store intent in DB immediately
        $intent = PaymentIntent::create([
            'user_id' => $user->id,
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'status' => StatusEnum::PENDING,
            'channel' => $channel,
        ]);

        event(new DepositInitiatedEvent(
           userId: $user->id,
           currency: $currency,
           amount: $amount,
           reference: $reference,
            provider: $channel,
        ));

        return $intent;
    }
}
