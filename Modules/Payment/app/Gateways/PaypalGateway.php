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
        $this->baseUrl = config('payment.providers.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $this->clientId = config('payment.providers.paypal.client_id');
        $this->secret   = config('payment.providers.paypal.secret');
    }

    protected function token(): string
    {
        return cache()->remember('paypal_token', 480, function () {
            $res = Http::asForm()
                ->withBasicAuth($this->clientId, $this->secret)
                ->post($this->baseUrl . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if (! $res->successful()) {
                throw new RuntimeException('PayPal auth failed');
            }

            return $res->json('access_token');
        });
    }


    public function initialize(array $data): array
    {
        $token = $this->token();

        $res = Http::withToken($token)
            ->post($this->baseUrl . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $data['reference'],
                    'custom_id'    => $data['reference'],
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

    public function payout(array $data): array
    {
        $token = $this->token();
        $destination = $data['destination'] ?? [];

        $res = Http::withToken($token)
            ->post($this->baseUrl . '/v1/payments/payouts', [
                'sender_batch_header' => [
                    'sender_batch_id' => $data['reference'],
                    'email_subject'   => 'You have a payout!',
                ],
                'items' => [[
                    'recipient_type' => $destination['type'] ?? 'EMAIL',
                    'amount' => [
                        'value'    => number_format($data['amount'], 2, '.', ''),
                        'currency' => strtoupper($data['currency']),
                    ],
                    'receiver'       => $destination['email'] ?? $destination['wallet_address'] ?? '',
                    'sender_item_id' => $data['reference'],
                ]],
            ]);

        if (! $res->successful()) {
            throw new RuntimeException('PayPal payout failed: ' . $res->body());
        }

        $batch = $res->json('batch_header');

        return [
            'provider_reference' => $batch['payout_batch_id'] ?? $data['reference'],
            'status'             => strtolower($batch['batch_status'] ?? 'pending'),
            'meta'               => $res->json(),
        ];
    }
}
