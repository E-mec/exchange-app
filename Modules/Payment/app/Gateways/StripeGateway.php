<?php

namespace Modules\Payment\app\Gateways;

use Modules\Payment\app\Interfaces\PaymentGatewayInterface;
use Modules\Payment\Enums\PaymentStatusEnum;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeGateway implements PaymentGatewayInterface
{

    public function __construct()
    {
        Stripe::setApiKey(config('payment.providers.stripe.secret'));
    }

    public function initialize(array $data): array
    {
        $session = Session::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($data['currency']),
                    'unit_amount' => (int) ($data['amount'] * 100),
                    'product_data' => [
                        'name' => 'Payment',
                    ],
                ],
                'quantity' => 1,
            ]],
            'customer_email' => $data['email'],
            'metadata' => $data['meta'] ?? [],
            'success_url' => $data['callbackUrl'] ?? config('app.url'),
            'cancel_url'  => $data['cancelUrl'] ?? config('app.url'),
        ]);

        return [
            'checkout_url'       => $session->url,
            'provider_reference' => $session->id,
            'meta'               => $session->toArray(),
        ];
    }

    public function verify(string $reference): array
    {
        $session = Session::retrieve($reference);

        return [
            'status' => $session->payment_status === 'paid'
                ? PaymentStatusEnum::SUCCESSFUL->value
                : PaymentStatusEnum::FAILED->value,

            'amount'   => $session->amount_total / 100,
            'currency' => strtoupper($session->currency),
            'provider_reference' => $session->id,
            'meta' => $session->toArray(),
        ];
    }

    public function payout(array $data): array
    {
        $destination = $data['destination'] ?? [];

        $payout = \Stripe\Payout::create([
            'amount'   => (int) ($data['amount'] * 100),
            'currency' => strtolower($data['currency']),
            'metadata' => array_merge($data['meta'] ?? [], [
                'reference' => $data['reference'],
            ]),
            'destination' => $destination['account_id'] ?? null,
        ]);

        return [
            'provider_reference' => $payout->id,
            'status'             => $payout->status ?? 'pending',
            'meta'               => $payout->toArray(),
        ];
    }
}
