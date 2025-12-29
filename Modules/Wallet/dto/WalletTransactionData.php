<?php

namespace Modules\Wallet\dto;

use Spatie\LaravelData\Data;

class WalletTransactionData extends Data
{
    public function __construct(
        public readonly int    $id,
        public readonly int    $wallet_id,
        public readonly int    $user_id,
        public readonly string $currency,
        public readonly string $type,
        public readonly string $before_balance,
        public readonly string $amount,
        public readonly string $balance_after,
        public readonly string $reference,
        public readonly string $idempotency_key,
        public readonly array  $meta = [],
        public readonly ?string $previous_hash = null,
        public readonly ?string $current_hash  = null,
        public readonly ?string $checksum      = null,
        public readonly string $created_at = '',
    )
    {}
}
