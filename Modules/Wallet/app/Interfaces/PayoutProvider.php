<?php

namespace Modules\Wallet\app\Interfaces;

interface PayoutProvider
{
    /**
     * Execute a payout to the specified destination.
     *
     * @param array $data Payout details including amount, currency, destination
     * @return array Provider response with transaction reference
     * @throws \Exception If payout fails
     */
    public function execute(array $data): array;

    /**
     * Get the provider name.
     */
    public function getName(): string;
}
