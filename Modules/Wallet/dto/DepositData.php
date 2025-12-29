<?php

namespace Modules\Wallet\dto;

use Spatie\LaravelData\Data;

class DepositData extends Data
{
    public function __construct(
        public readonly int    $amount,
        public readonly string $reference,
        public readonly string $channel,
        public readonly string $currency
    )
    {
    }
}
