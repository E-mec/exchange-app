<?php

namespace Modules\Wallet\actions;

use Illuminate\Database\Eloquent\Collection;
use Modules\Wallet\dto\WalletData;
use Modules\Wallet\Models\Wallet;

class GetUserWalletsAction
{
    public function handle(int $userId): Collection|\Illuminate\Support\Collection
    {
      return Wallet::query()
            ->where('user_id', $userId)
            ->get()
            ->map(fn (Wallet $wallet) => WalletData::from($wallet));
    }
}
