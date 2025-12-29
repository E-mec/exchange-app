<?php

namespace Modules\Wallet\actions;

use Modules\Wallet\Models\WalletTransaction;

class IdempotencyCheckAction
{
    public function execute(string $idempotencyKey)
    {
        return WalletTransaction::where('idempotency_key', $idempotencyKey)->first();
    }
}
