<?php

namespace Modules\Payment\app\Interfaces;

interface PaymentGatewayInterface {
    /**
     * Initialize an inbound payment (deposit) with the provider.
     */
    public function initialize(array $data): array;

    /**
     * Verify a payment (used by webhook / manual checks).
     */
    public function verify(string $reference): array;

    /**
     * Initiate an outbound payout/transfer to a destination.
     *
     * @param array $data Payout details: amount, currency, destination, reference, meta
     * @return array Provider response: provider_reference, status, meta
     * @throws \RuntimeException If the payout initiation fails
     */
    public function payout(array $data): array;
}
