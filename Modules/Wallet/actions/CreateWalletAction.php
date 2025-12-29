<?php

namespace Modules\Wallet\actions;

use Modules\Auth\Models\User;
use Modules\Wallet\enums\CurrencyEnum;
use Modules\Wallet\Models\Wallet;

class CreateWalletAction
{
    public function execute(User $user, CurrencyEnum $currency): void
    {
        Wallet::create([
            'user_id' => $user->id,
            'currency' => $currency,
        ]);
    }
}
