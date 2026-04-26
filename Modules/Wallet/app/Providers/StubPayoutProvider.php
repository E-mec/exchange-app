<?php

namespace Modules\Wallet\app\Providers;

use Modules\Wallet\app\Interfaces\PayoutProvider;
use Exception;

/**
 * Stub implementation for payout provider.
 * Replace with actual provider implementation (Paystack, Flutterwave, etc.)
 */
final class StubPayoutProvider implements PayoutProvider
{
    /**
     * Execute a payout via external provider.
     * This is a stub - replace with actual API calls.
     *
     * @throws Exception
     */
    public function execute(array $data): array
    {
        // Stub implementation - should call actual payment provider API
        // Example:
        // $response = Http::post('https://api.paystack.co/transfer', [
        //     'source' => 'balance',
        //     'amount' => $data['amount'] * 100,
        //     'recipient_code' => $data['destination']['recipient_code'],
        // ]);

        throw new Exception('PayoutProvider not implemented. Replace with actual provider implementation.');
    }

    public function getName(): string
    {
        return 'stub';
    }
}
