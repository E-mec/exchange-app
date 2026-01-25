<?php

namespace Modules\Payment\app\Interfaces;

interface PaymentGatewayInterface {
    /**
     * Initialize a payment with the provider
     */
    public function initialize(array $data): array;

    /**
     * Verify a payment (used by webhook / manual checks)
     */
    public function verify(string $reference): array;
}
