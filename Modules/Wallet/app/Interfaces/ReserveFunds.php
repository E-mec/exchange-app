<?php

namespace Modules\Wallet\app\Interfaces;

use Modules\Wallet\Models\WalletTransaction;

interface ReserveFunds {
    public function execute(array $data): WalletTransaction;

}
