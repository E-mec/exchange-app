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
                'redirect_url' => config('payment.providers.flutterwave.redirect_url')
                    . '?reference=' . $data['reference']
                    . '&gateway=flutterwave',   // same pattern as Stripe
                'customer' => [
                    'email' => $data['email'],
                ],
                'meta' => $data['meta'] ?? [],
            ]);

        logger('response', [
            'result' => $res->json(),
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
            ->get("https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref={$reference}");

        logger('verify response', [
            'result' => $res->json(),
        ]);

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

    public function payout(array $data): array
    {
        $destination = $data['destination'] ?? [];

        $res = Http::withToken($this->secret)
            ->post('https://api.flutterwave.com/v3/transfers', [
                'account_bank'   => $destination['bank_code'] ?? null,
                'account_number' => $destination['account_number'] ?? null,
                'amount'         => $data['amount'],
                'currency'       => $data['currency'],
                'reference'      => $data['reference'],
                'narration'      => $data['meta']['reason'] ?? 'Withdrawal payout',
                'meta'           => $data['meta'] ?? [],
            ]);

        if (! $res->successful()) {
            throw new RuntimeException('Flutterwave transfer failed: ' . $res->body());
        }

        $d = $res->json('data');

        return [
            'provider_reference' => (string) ($d['id'] ?? $data['reference']),
            'status'             => $d['status'] ?? 'pending',
            'meta'               => $d,
        ];
    }
}
