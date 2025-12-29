<?php

namespace Modules\Wallet\actions;

use App\Exceptions\CustomException;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Enums\TransactionTypeEnum;
use Modules\Wallet\Models\WalletTransaction;

final class FinalizeDebitAction
{
    public function __construct(
        protected ApplyTransactionOrchestratorAction $orchestrator
    ) {}

    public function execute(array $data): WalletTransaction
    {
        return DB::transaction(function () use ($data) {

            /** @var WalletTransaction $reserveTx */
            $reserveTx = WalletTransaction::query()
                ->where('reference', $data['reference'])
                ->where('type', TransactionTypeEnum::RESERVE)
                ->lockForUpdate()
                ->first();

            if (! $reserveTx) {
                throw new CustomException('Reserve transaction not found.');
            }

            if ($reserveTx->is_finalized) {
                throw new CustomException('Reserve already finalized.');
            }

            // Apply actual debit
            $debitTx = $this->orchestrator->execute([
                'walletId'       => $reserveTx->wallet_id,
                'userId'         => $reserveTx->user_id,
                'currency'       => $reserveTx->currency,
                'amount'         => $reserveTx->amount,
                'type'           => TransactionTypeEnum::DEBIT,
                'reference'      => $reserveTx->reference,
                'idempotencyKey' => $data['idempotencyKey'],
                'meta'           => array_merge(
                    $reserveTx->meta ?? [],
                    ['reserve_tx_id' => $reserveTx->id]
                ),
            ]);

            // Mark reserve as consumed
            $reserveTx->update([
                'is_finalized' => true,
            ]);

            return $debitTx;
        });
    }
}
