<?php

namespace Modules\Payment\Webhooks;

use Illuminate\Http\Request;
use Modules\Payment\app\Interfaces\WebhookVerifierInterface;
use Stripe\Webhook;

class StripeVerifier implements WebhookVerifierInterface
{
    public function verify(Request $request): void
    {
        Webhook::constructEvent(
            $request->getContent(),
            $request->header('Stripe-Signature'),
            config('payment.providers.stripe.webhook_secret')
        );
    }
}
