<?php

namespace Modules\Wallet\app\Listeners;

use Modules\Payment\app\Events\PaymentFailed;
use Modules\Payment\Enums\PaymentTypeEnum;
use Modules\Wallet\app\Events\WithdrawalFailed;

class FailWithdrawalOnPayoutFailedListener
{
    public function handle(PaymentFailed $event): void
    {
        if ($event->payment->type !== PaymentTypeEnum::PAYOUT) {
            return;
        }

        // Translate PaymentFailed → WithdrawalFailed
        // This triggers ReleaseWithdrawalFundsListener → FailWithdrawalAction
        // which releases reserved funds and marks the withdrawal FAILED
        WithdrawalFailed::dispatch(
            $event->payment->reference,
            'payment_provider_failed'
        );
    }
}
