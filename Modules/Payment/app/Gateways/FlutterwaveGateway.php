<?php

namespace Modules\Payment\app\Gateways;

use Illuminate\Support\Facades\Http;
use Modules\Payment\app\Interfaces\PaymentGatewayInterface;
use Modules\Payment\Enums\PaymentStatusEnum;
use RuntimeException;

class FlutterwaveGateway implements PaymentGatewayInterface
{

    protected string $secret;

    public function __construct()
    {
        $this->secret = config('payment.providers.flutterwave.secret');
    }

    public function initialize(array $data): array
    {
        $res = Http::withToken($this->secret)
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref'   => $data['reference'],
                'amount'   => $data['amount'],
                'currency' => $data['currency'],
                'customer' => [
                    'email' => $data['email'],
                ],
                'meta' => $data['meta'] ?? [],
            ]);

        if (! $res->successful()) {
            throw new RuntimeException('Flutterwave init failed');
        }

        $d = $res->json('data');

        return [
            'checkout_url'       => $d['link'],
            'provider_reference' => $data['reference'],
            'meta' => $d,
        ];
    }

    public function verify(string $reference): array
    {
        $res = Http::withToken($this->secret)
            ->get("https://api.flutterwave.com/v3/transactions/{$reference}/verify");

        if (! $res->successful()) {
            throw new RuntimeException('Flutterwave verify failed');
        }

        $d = $res->json('data');

        return [
            'status' => $d['status'] === 'successful'
                ? PaymentStatusEnum::SUCCESSFUL->value
                : PaymentStatusEnum::FAILED->value,
            'amount'   => $d['amount'],
            'currency' => $d['currency'],
            'provider_reference' => $d['tx_ref'],
            'meta' => $d,
        ];
    }
}
