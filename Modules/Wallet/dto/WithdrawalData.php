<?php

namespace Modules\Wallet\dto;

use Modules\Auth\dtos\ResponseDto\UserData;
use Modules\Wallet\enums\CurrencyEnum;
use Spatie\LaravelData\Data;

class WithdrawalData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly float $amount,
        public readonly string $status,
        public readonly CurrencyEnum $currency,
        public readonly string $reference,
        public readonly WalletData $wallet,
        public readonly UserData $user,
    ){}
}
