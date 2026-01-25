<?php

namespace Modules\Payment\Webhooks;

use Illuminate\Http\Request;
use Modules\Payment\app\Interfaces\WebhookVerifierInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class PaystackVerifier implements WebhookVerifierInterface
{

    public function verify(Request $request): void
    {
        $signature = $request->header('x-paystack-signature');
        $computed  = hash_hmac(
            'sha512',
            $request->getContent(),
            config('payment.providers.paystack.webhook_secret')
        );

        if (! hash_equals($computed, $signature)) {
            throw new UnauthorizedHttpException('Paystack', 'Invalid webhook signature');
        }
    }
}
