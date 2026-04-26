<?php

namespace Modules\Wallet\dto;

use App\Enums\StatusEnum;
use Spatie\LaravelData\Data;

class DepositData extends Data
{
    public function __construct(
        public readonly string $amount,
        public readonly string $reference,
        public readonly string $channel,
        public readonly string $currency,
        public readonly StatusEnum $status,
        public readonly ?string $checkout_url,
    )
    {
    }
}
