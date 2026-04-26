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

    public function payout(array $data): array
    {
        // Create transfer recipient first
        $destination = $data['destination'] ?? [];

        $recipientResponse = Http::withToken($this->secret)
            ->post($this->baseUrl . '/transferrecipient', [
                'type'           => $destination['type'] ?? 'nuban',
                'name'           => $destination['account_name'] ?? 'Recipient',
                'account_number' => $destination['account_number'],
                'bank_code'      => $destination['bank_code'],
                'currency'       => $data['currency'],
            ]);

        if (! $recipientResponse->successful()) {
            throw new RuntimeException('Paystack recipient creation failed: ' . $recipientResponse->body());
        }

        $recipientCode = $recipientResponse->json('data.recipient_code');

        // Initiate transfer
        $response = Http::withToken($this->secret)
            ->post($this->baseUrl . '/transfer', [
                'source'    => 'balance',
                'amount'    => (int) ($data['amount'] * 100), // kobo
                'recipient' => $recipientCode,
                'reference' => $data['reference'],
                'reason'    => $data['meta']['reason'] ?? 'Withdrawal payout',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Paystack transfer failed: ' . $response->body());
        }

        $payload = $response->json('data');

        return [
            'provider_reference' => $payload['transfer_code'] ?? $payload['reference'],
            'status'             => $payload['status'] ?? 'pending',
            'meta'               => $payload,
        ];
    }
}
