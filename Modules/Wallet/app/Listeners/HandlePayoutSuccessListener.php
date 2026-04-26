<?php

namespace Modules\Wallet\app\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Payment\app\Events\PaymentSuccessful;
use Modules\Payment\Enums\PaymentTypeEnum;
use Modules\Wallet\actions\FinalizeWithdrawalAction;
use Modules\Wallet\Models\Withdrawal;

/**
 * Listens to PaymentSuccessful events from the Payment module.
 * When a payout-type payment succeeds, finalizes the corresponding withdrawal.
 */
final class HandlePayoutSuccessListener
{
    public function __construct(
        protected FinalizeWithdrawalAction $finalize
    ) {}

    public function handle(PaymentSuccessful $event): void
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
            Log::error('HandlePayoutSuccessListener: Withdrawal not found for payout payment.', [
                'payment_id'        => $payment->id,
                'payment_reference' => $payment->reference,
            ]);
            return;
        }

        // Update withdrawal with final provider reference
        $withdrawal->update([
            'provider_reference' => $payment->provider_reference,
        ]);

        $this->finalize->execute($withdrawal);

        Log::info('HandlePayoutSuccessListener: Withdrawal finalized.', [
            'withdrawal_reference' => $withdrawal->reference,
            'payment_id'           => $payment->id,
        ]);
    }
}
