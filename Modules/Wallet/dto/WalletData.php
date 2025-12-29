<?php

namespace Modules\Wallet\dto;

use Modules\Wallet\enums\CurrencyEnum;
use Spatie\LaravelData\Data;

class WalletData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly ?CurrencyEnum $currency,
        public readonly string $available_balance,
        public readonly string $reserved_balance,
        public readonly string $ledger_balance,
    ){}
}
