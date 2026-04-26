<?php

namespace Modules\Wallet\app\Listeners;

use Modules\Wallet\actions\FailWithdrawalAction;
use Modules\Wallet\app\Events\WithdrawalFailed;
use Modules\Wallet\Models\Withdrawal;

final class ReleaseWithdrawalFundsListener
{
    public function __construct(
        protected FailWithdrawalAction $failWithdrawal
    ) {}

    public function handle(WithdrawalFailed $event): void
    {
        $withdrawal = Withdrawal::query()
            ->where('reference', $event->reference)
            ->first();

        if (! $withdrawal) {
            return;
        }

        $this->failWithdrawal->execute($withdrawal, $event->reason);
    }
}
