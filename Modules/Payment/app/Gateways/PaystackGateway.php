<?php

namespace Modules\Payment\app\Gateways;

use Illuminate\Support\Facades\Http;
use Modules\Payment\app\Interfaces\PaymentGatewayInterface;
use Modules\Payment\Enums\PaymentStatusEnum;
use RuntimeException;

class PaystackGateway implements PaymentGatewayInterface
{
    protected string $baseUrl;
    protected string $secret;

    public function __construct()
    {
        $this->baseUrl = 'https://api.paystack.co';
        $this->secret  = config('payment.providers.paystack.secret');
    }

    public function initialize(array $data): array
    {
        $response = Http::withToken($this->secret)
            ->post($this->baseUrl . '/transaction/initialize', [
                'email'        => $data['email'],
                'amount'       => (int) ($data['amount'] * 100), // kobo
                'reference'    => $data['reference'],
                'currency'     => $data['currency'],
                'metadata'     => $data['meta'] ?? [],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Paystack initialization failed');
        }

        $payload = $response->json('data');

        return [
            'checkout_url'       => $payload['authorization_url'],
            'provider_reference' => $payload['reference'],
            'meta'               => $payload,
        ];
    }

    public function verify(string $reference): array
    {
        $response = Http::withToken($this->secret)
            ->get($this->baseUrl . "/transaction/verify/{$reference}");

        if (! $response->successful()) {
            throw new RuntimeException('Paystack verification failed');
        }

        $data = $response->json('data');

        return [
            'status' => $data['status'] === 'success'
                ? PaymentStatusEnum::SUCCESSFUL->value
                : PaymentStatusEnum::FAILED->value,

            'amount'   => $data['amount'] / 100,
            'currency' => $data['currency'],
            'provider_reference' => $data['reference'],
            'meta' => $data,
        ];
    }
}
