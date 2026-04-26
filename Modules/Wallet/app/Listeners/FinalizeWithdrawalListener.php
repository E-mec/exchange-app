<?php

namespace Modules\Wallet\app\Listeners;

use Modules\Wallet\actions\FinalizeWithdrawalAction;
use Modules\Wallet\app\Events\WithdrawalSucceeded;
use Modules\Wallet\Models\Withdrawal;

final class FinalizeWithdrawalListener
{
    public function __construct(
        protected FinalizeWithdrawalAction $finalize
    ) {}

    public function handle(WithdrawalSucceeded $event): void
    {
        $withdrawal = Withdrawal::query()
            ->where('reference', $event->reference)
            ->first();

        if (! $withdrawal) {
            return;
        }

        $this->finalize->execute($withdrawal);
    }
}
