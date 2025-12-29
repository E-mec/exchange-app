<?php

namespace Modules\Wallet\actions;

use Modules\Wallet\Models\Wallet;

class UpdateWalletBalanceAction
{
    /**
     * Update wallet balances (available + ledger snapshot).
     */
    public function execute(Wallet $wallet, string $newAvailable, string $newLedger = null): Wallet
    {
        $payload = [
            'available_balance' => $newAvailable,
        ];

        if ($newLedger !== null) {
            $payload['ledger_balance'] = $newLedger;
        }

        $wallet->update($payload);

        return $wallet->refresh();
    }
}
