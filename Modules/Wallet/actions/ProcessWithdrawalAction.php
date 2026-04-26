<?php

namespace Modules\Wallet\actions;

use Modules\Wallet\Models\Withdrawal;
use Modules\Wallet\Enums\WithdrawalStatusEnum;
use App\Exceptions\CustomException;
use Modules\Wallet\app\Jobs\ProcessWithdrawalJob;

final class ProcessWithdrawalAction
{
    /**
     * @throws CustomException
     */
    public function execute(Withdrawal $withdrawal): Withdrawal
    {
        if ($withdrawal->status !== WithdrawalStatusEnum::PENDING) {
            throw new CustomException('Withdrawal not in pending state');
        }

        $withdrawal->update([
            'status' => WithdrawalStatusEnum::PROCESSING,
        ]);

        // Dispatch job to process payout asynchronously
        ProcessWithdrawalJob::dispatch($withdrawal->reference);

        return $withdrawal->refresh();
    }
}
