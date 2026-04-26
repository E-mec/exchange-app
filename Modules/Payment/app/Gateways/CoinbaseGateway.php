<?php

namespace Modules\Payment\app\Gateways;

use Illuminate\Support\Facades\Http;
use Modules\Payment\app\Interfaces\PaymentGatewayInterface;
use Modules\Payment\dtos\PaymentInitializationDto;
use Modules\Payment\dtos\PaymentVerificationDto;
use Modules\Payment\Enums\PaymentStatusEnum;
use RuntimeException;

class CoinbaseGateway implements PaymentGatewayInterface
{
    public function initialize(array $data): array
    {
        $response = Http::withHeaders([
            'X-CC-Api-Key' => config('payment.coinbase.api_key'),
            'X-CC-Version' => '2018-03-22',
        ])->post('https://api.commerce.coinbase.com/charges', [
            'name' => 'Crypto Payment',
            'pricing_type' => 'fixed_price',
            'local_price' => [
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ],
            'metadata' => [
                'reference' => $data['reference'],
                ...$data['metadata'],
            ],
            'redirect_url' => $data['callbackUrl'],
            'cancel_url' => $data['cancelUrl'],
        ]);

        $data = $response->json('data');

        return [
            'checkout_url'       => $data['hosted_url'],
            'provider_reference' => $data['code'],
            'meta'               => $data,
        ];
    }

    public function verify(string $reference): array
    {
        $res = Http::withHeaders([
            'X-CC-Api-Key' => config('payment.coinbase.api_key'),
            'X-CC-Version' => '2018-03-22',
        ])->get("https://api.commerce.coinbase.com/charges/{$reference}");

        if (! $res->successful()) {
            throw new RuntimeException('Coinbase verify failed');
        }

        $data = $res->json('data');

        return [
            'status' => collect($data['timeline'])
                ->contains(fn ($e) => $e['status'] === 'COMPLETED')
                ? PaymentStatusEnum::SUCCESSFUL->value
                : PaymentStatusEnum::PENDING->value,

            'provider_reference' => $data['code'],
            'meta' => $data,
        ];
    }

    public function payout(array $data): array
    {
        $destination = $data['destination'] ?? [];

        $response = Http::withHeaders([
            'X-CC-Api-Key' => config('payment.coinbase.api_key'),
            'X-CC-Version' => '2018-03-22',
        ])->post('https://api.coinbase.com/v2/accounts/' . config('payment.coinbase.account_id') . '/transactions', [
            'type'     => 'send',
            'to'       => $destination['wallet_address'] ?? '',
            'amount'   => $data['amount'],
            'currency' => $data['currency'],
            'idem'     => $data['reference'],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Coinbase payout failed: ' . $response->body());
        }

        $txn = $response->json('data');

        return [
            'provider_reference' => $txn['id'] ?? $data['reference'],
            'status'             => strtolower($txn['status'] ?? 'pending'),
            'meta'               => $txn,
        ];
    }
}
