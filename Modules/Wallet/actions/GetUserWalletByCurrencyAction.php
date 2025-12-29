<?php

namespace Modules\Wallet\actions;

use Modules\Wallet\dto\WalletData;
use Modules\Wallet\enums\CurrencyEnum;
use Modules\Wallet\Models\Wallet;

class GetUserWalletByCurrencyAction
{
    public function execute(int $userId, CurrencyEnum $currency): WalletData
    {
        $wallet = Wallet::query()
            ->where('user_id', $userId)
            ->where('currency', $currency)
            ->firstOrFail();

        return WalletData::from($wallet);
    }
}
