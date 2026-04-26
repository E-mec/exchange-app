<?php

namespace Modules\Wallet\actions;

use App\Exceptions\CustomException;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\enums\TransactionTypeEnum;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Wallet\Models\WalletTransaction;
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

        if ($withdrawal->status === WithdrawalStatusEnum::FAILED) {
            throw new CustomException('Failed withdrawals cannot be finalized');
        }

        DB::transaction(function () use ($withdrawal) {
            $lockedWithdrawal = Withdrawal::query()
                ->whereKey($withdrawal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedWithdrawal->status === WithdrawalStatusEnum::SUCCESS) {
                return;
            }

            if ($lockedWithdrawal->status === WithdrawalStatusEnum::FAILED) {
                throw new CustomException('Failed withdrawals cannot be finalized');
            }

            $reserveTxn = WalletTransaction::query()
                ->where('reference', $lockedWithdrawal->reference)
                ->where('type', TransactionTypeEnum::RESERVE)
                ->lockForUpdate()
                ->first();

            if (! $reserveTxn) {
                throw new CustomException('Reserve transaction not found.');
            }

            if ($reserveTxn->is_finalized) {
                $debitExists = WalletTransaction::query()
                    ->where('wallet_id', $lockedWithdrawal->wallet_id)
                    ->where('reference', $lockedWithdrawal->reference)
                    ->where('type', TransactionTypeEnum::DEBIT)
                    ->exists();

                if ($debitExists) {
                    $lockedWithdrawal->update([
                        'status' => WithdrawalStatusEnum::SUCCESS,
                        'failure_reason' => null,
                    ]);

                    return;
                }

                throw new CustomException('Reserve already finalized.');
            }

            $this->apply->execute([
                'walletId'       => $lockedWithdrawal->wallet_id,
                'currency'       => $lockedWithdrawal->currency,
                'amount'         => $lockedWithdrawal->amount,
                'type'           => TransactionTypeEnum::DEBIT,
                'reference'      => $lockedWithdrawal->reference,
                'idempotencyKey' => $lockedWithdrawal->reference . ':debit',
                'meta' => [
                    'withdrawal_id' => $lockedWithdrawal->id,
                ],
            ]);

            $reserveTxn->update([
                'is_finalized' => true,
                'finalized_at' => now(),
            ]);

            $lockedWithdrawal->update([
                'status' => WithdrawalStatusEnum::SUCCESS,
                'failure_reason' => null,
            ]);
        });
    }
}
