<?php

namespace Modules\Payment\app\Gateways;

use Illuminate\Support\Facades\Http;
use Modules\Payment\app\Interfaces\PaymentGatewayInterface;
use Modules\Payment\Enums\PaymentStatusEnum;
use RuntimeException;

class PaypalGateway implements PaymentGatewayInterface
{

    protected string $baseUrl;
    protected string $clientId;
    protected string $secret;

    public function __construct()
    {
        $this->baseUrl  = config('payment.providers.paypal.sandbox')
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        $this->clientId = config('payment.providers.paypal.client_id');
        $this->secret   = config('payment.providers.paypal.secret');
    }

    protected function token(): string
    {
        $res = Http::asForm()
            ->withBasicAuth($this->clientId, $this->secret)
            ->post($this->baseUrl . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $res->successful()) {
            throw new RuntimeException('PayPal auth failed');
        }

        return $res->json('access_token');
    }

    public function initialize(array $data): array
    {
        $token = $this->token();

        $res = Http::withToken($token)
            ->post($this->baseUrl . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $data['reference'],
                    'amount' => [
                        'currency_code' => strtoupper($data['currency']),
                        'value' => number_format($data['amount'], 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => $data['callbackUrl'] ?? config('app.url'),
                    'cancel_url' => $data['cancelUrl'] ?? config('app.url'),
                ],
            ]);

        if (! $res->successful()) {
            throw new RuntimeException('PayPal init failed');
        }

        $order = $res->json();
        $approve = collect($order['links'])
            ->firstWhere('rel', 'approve');

        return [
            'checkout_url'       => $approve['href'],
            'provider_reference' => $order['id'],
            'meta'               => $order,
        ];
    }

    public function verify(string $reference): array
    {
        $token = $this->token();

        $res = Http::withToken($token)
            ->get($this->baseUrl . "/v2/checkout/orders/{$reference}");

        if (! $res->successful()) {
            throw new RuntimeException('PayPal verify failed');
        }

        $order = $res->json();

        return [
            'status' => $order['status'] === 'COMPLETED'
                ? PaymentStatusEnum::SUCCESSFUL->value
                : PaymentStatusEnum::FAILED->value,

            'amount'   => $order['purchase_units'][0]['amount']['value'],
            'currency' => $order['purchase_units'][0]['amount']['currency_code'],
            'provider_reference' => $order['id'],
            'meta' => $order,
        ];
    }
}
