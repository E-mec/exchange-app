<?php

namespace Modules\Wallet\app\Interfaces;

use Modules\Wallet\Models\WalletTransaction;

interface ReleaseReservedFunds {
    public function execute(array $data): WalletTransaction;
}
