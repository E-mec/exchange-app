<?php

namespace Modules\Wallet\app\Listeners;

use Illuminate\Support\Facades\Log;
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
            Log::error('FinalizeWithdrawalListener: Withdrawal not found for reference, possible money leak.', [
                'reference' => $event->reference,
            ]);
            return;
        }

        $this->finalize->execute($withdrawal);
    }
}
