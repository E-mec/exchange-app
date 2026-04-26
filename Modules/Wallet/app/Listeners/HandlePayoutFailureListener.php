<?php

namespace Modules\Wallet\app\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Payment\app\Events\PaymentFailed;
use Modules\Payment\Enums\PaymentTypeEnum;
use Modules\Wallet\actions\FailWithdrawalAction;
use Modules\Wallet\Models\Withdrawal;

/**
 * Listens to PaymentFailed events from the Payment module.
 * When a payout-type payment fails, fails the corresponding withdrawal
 * and releases reserved funds.
 */
final class HandlePayoutFailureListener
{
    public function __construct(
        protected FailWithdrawalAction $failWithdrawal
    ) {}

    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;

        // Only handle payout-type payments
        if ($payment->type !== PaymentTypeEnum::PAYOUT) {
            return;
        }

        $withdrawal = Withdrawal::query()
            ->where('reference', $payment->reference)
            ->first();

        if (! $withdrawal) {
            Log::error('HandlePayoutFailureListener: Withdrawal not found for payout payment.', [
                'payment_id'        => $payment->id,
                'payment_reference' => $payment->reference,
            ]);
            return;
        }

        $reason = 'Payout failed via provider: ' . ($payment->provider->value ?? 'unknown');

        $this->failWithdrawal->execute($withdrawal, $reason);

        Log::info('HandlePayoutFailureListener: Withdrawal failed and funds released.', [
            'withdrawal_reference' => $withdrawal->reference,
            'payment_id'           => $payment->id,
            'reason'               => $reason,
        ]);
    }
}
