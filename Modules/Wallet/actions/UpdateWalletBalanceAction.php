<?php

namespace Modules\Wallet\actions;

use Modules\Wallet\Models\Wallet;

final class UpdateWalletBalanceAction
{
    /**
     * Update wallet balances (available + ledger snapshot).
     */
    public function execute(Wallet $wallet, string $available, string $reserved = null, string $ledger = null): Wallet
    {
        $payload = ['available_balance' => $available];
        if ($reserved !== null) $payload['reserved_balance'] = $reserved;
        if ($ledger !== null) $payload['ledger_balance'] = $ledger;

        $wallet->update($payload);



        return $wallet->refresh();
    }


}
