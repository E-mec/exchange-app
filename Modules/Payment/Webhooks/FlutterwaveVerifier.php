<?php

namespace Modules\Payment\Webhooks;

use Illuminate\Http\Request;
use Modules\Payment\app\Interfaces\WebhookVerifierInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class FlutterwaveVerifier implements WebhookVerifierInterface
{

    public function verify(Request $request): void
    {
        if (
            $request->header('verif-hash') !==
            config('payment.providers.flutterwave.webhook_secret')
        ) {
            throw new UnauthorizedHttpException('Flutterwave', 'Invalid webhook signature');
        }
    }
}
