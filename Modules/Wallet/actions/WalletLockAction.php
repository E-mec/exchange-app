<?php

namespace Modules\Wallet\actions;

use Modules\Wallet\Models\Wallet;

class WalletLockAction
{
    public function execute(int $walletId): Wallet
    {
        return Wallet::where('id', $walletId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
