<?php

namespace Modules\Payment\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\Payment\app\Interfaces\WebhookVerifierInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class PaypalVerifier implements WebhookVerifierInterface
{
    public function verify(Request $request): void
    {
        $baseUrl = config('payment.providers.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $response = Http::withBasicAuth(
            config('payment.providers.paypal.client_id'),
            config('payment.providers.paypal.secret')
        )
            ->post($baseUrl . '/v1/notifications/verify-webhook-signature', [
                'auth_algo'         => $request->header('PAYPAL-AUTH-ALGO'),
                'cert_url'          => $request->header('PAYPAL-CERT-URL'),
                'transmission_id'   => $request->header('PAYPAL-TRANSMISSION-ID'),
                'transmission_sig'  => $request->header('PAYPAL-TRANSMISSION-SIG'),
                'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
                'webhook_id'        => config('payment.providers.paypal.webhook_id'),
                'webhook_event'     => $request->all(),
            ]);

        if (! $response->successful()) {
            throw new UnauthorizedHttpException('PayPal', 'Webhook verification failed');
        }

        if ($response->json('verification_status') !== 'SUCCESS') {
            throw new UnauthorizedHttpException('PayPal', 'Invalid PayPal webhook');
        }
    }
}
