<?php

namespace Modules\Payment\Webhooks;

use Illuminate\Http\Request;
use Modules\Payment\app\Interfaces\WebhookVerifierInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class CoinbaseVerifier implements WebhookVerifierInterface
{

    public function verify(Request $request): void
    {
        $signature = $request->header('X-CC-Webhook-Signature');
        $computed  = hash_hmac(
            'sha256',
            $request->getContent(),
            config('payment.providers.coinbase.webhook_secret')
        );

        if (! hash_equals($computed, $signature)) {
            throw new UnauthorizedHttpException('Coinbase', 'Invalid webhook signature');
        }
    }
}
