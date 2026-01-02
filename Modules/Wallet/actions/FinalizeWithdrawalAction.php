<?php

namespace Modules\Wallet\actions;

use Illuminate\Support\Facades\DB;
use Modules\Wallet\enums\TransactionTypeEnum;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Wallet\Models\Withdrawal;

final class FinalizeWithdrawalAction
{
    public function __construct(
        protected ApplyTransactionOrchestratorAction $apply
    ) {}

    public function execute(Withdrawal $withdrawal): void
    {
        if ($withdrawal->status === WithdrawalStatusEnum::SUCCESS) {
            return; // idempotent, no double debit
        }

        DB::transaction(function () use ($withdrawal) {

            $this->apply->execute([
                'walletId'       => $withdrawal->wallet_id,
                'currency'       => $withdrawal->currency,
                'amount'         => $withdrawal->amount,
                'type'           => TransactionTypeEnum::DEBIT,
                'reference'      => $withdrawal->reference,
                'idempotencyKey' => $withdrawal->reference . ':debit',
                'meta' => [
                    'withdrawal_id' => $withdrawal->id,
                ],
            ]);

            $withdrawal->update([
                'status' => WithdrawalStatusEnum::SUCCESS,
            ]);
        });
    }
}

