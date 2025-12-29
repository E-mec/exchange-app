<?php

namespace Modules\Wallet\actions;

use Modules\Wallet\Models\WalletTransaction;

class WriteLedgerEntryAction
{
    public function execute(array $data): WalletTransaction
    {
        return WalletTransaction::create($data);
    }
}
