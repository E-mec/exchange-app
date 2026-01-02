<?php


namespace Modules\Wallet\actions;

use Modules\Wallet\Models\Withdrawal;
use Modules\Wallet\Enums\WithdrawalStatusEnum;
use App\Exceptions\CustomException;

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

// 🔌 Call external provider here (Paystack, Flutterwave, etc)
// $providerResponse = Provider::payout(...);

        $withdrawal->update([
            'status' => WithdrawalStatusEnum::PROCESSING,
        ]);

        return $withdrawal->refresh();
    }
}
