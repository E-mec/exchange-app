<?php

namespace Modules\Wallet\app\Listeners;

use Modules\Payment\app\Events\PaymentSuccessful;
use Modules\Payment\Enums\PaymentTypeEnum;
use Modules\Wallet\app\Events\WithdrawalSucceeded;

class FinalizeWithdrawalOnPayoutSuccessListener
{
    public function handle(PaymentSuccessful $event): void
    {
        // Only act on outbound payout confirmations
        if ($event->payment->type !== PaymentTypeEnum::PAYOUT) {
            return;
        }

        // Translate PaymentSuccessful → WithdrawalSucceeded
        // This triggers FinalizeWithdrawalListener → FinalizeWithdrawalAction
        // which debits reserved funds and marks the withdrawal SUCCESS
        WithdrawalSucceeded::dispatch($event->payment->reference);
    }
}
