<?php

namespace Modules\Wallet\app\Listeners;

use Modules\Wallet\actions\ApplyTransactionOrchestratorAction;
use Modules\Wallet\app\Events\WithdrawalSucceeded;
use Modules\Wallet\enums\TransactionTypeEnum;
use Modules\Wallet\Models\WalletTransaction;

final class FinalizeWithdrawalListener
{
    public function __construct(
        protected ApplyTransactionOrchestratorAction $orchestrator
    ) {}

    public function handle(WithdrawalSucceeded $event): void
    {
        $reserveTxn = WalletTransaction::where('reference', $event->reference)
            ->where('type', TransactionTypeEnum::RESERVE)
            ->lockForUpdate()
            ->first();

        if (! $reserveTxn || $reserveTxn->is_finalized) {
            return; // idempotent, safe replay
        }

        $this->orchestrator->execute([
            'walletId'       => $reserveTxn->wallet_id,
            'currency'       => $reserveTxn->currency,
            'amount'         => $reserveTxn->amount,
            'type'           => TransactionTypeEnum::DEBIT,
            'reference'      => $reserveTxn->reference,
            'idempotencyKey' => $reserveTxn->idempotency_key . ':finalize',
            'meta' => [
                'withdrawal' => true,
            ],
        ]);

        $reserveTxn->update(['is_finalized' => true]);
    }
}
