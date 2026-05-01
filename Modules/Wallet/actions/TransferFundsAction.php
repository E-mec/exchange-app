<?php

namespace Modules\Wallet\actions;

use App\Actions\VerifyPin;
use App\Exceptions\CustomException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Wallet\enums\TransactionTypeEnum;
use Modules\Wallet\Models\Wallet;

final class TransferFundsAction
{
    public function __construct(
        protected ReserveFundsAction $reserve,
        protected ApplyTransactionOrchestratorAction $apply,
    ) {}

    public function execute(array $payload): array
    {
        app(VerifyPin::class)->execute($payload['pin']);

        return DB::transaction(function () use ($payload) {

            $fromWallet = Wallet::forUser(
                auth()->id(),
                $payload['from_currency']
            );

            $toWallet = Wallet::forUser(
                $payload['to_user_id'],
                $payload['to_currency']
            );

            if (! $fromWallet) {
                throw new CustomException('Sender wallet not found');
            }

            if (! $toWallet) {
                throw new CustomException('Destination wallet not found');
            }

            $reference = 'TRF-' . Str::uuid();
            $baseKey   = $payload['idempotency_key'];

            /**
             * STEP 1: Reserve sender funds
             */
            $this->reserve->execute([
                'walletId'       => $fromWallet->id,
                'userId'         => auth()->id(),
                'currency'       => $fromWallet->currency,
                'amount'         => $payload['amount'],
                'reference'      => $reference,
                'idempotencyKey' =>  $baseKey . ':reserve',
                'meta' => [
                    'to_user' => $toWallet->user_id,
                ],
            ]);

            /**
             * STEP 2: Credit receiver (FINALIZED)
             */
            $creditTx = $this->apply->execute([
                'walletId'       => $toWallet->id,
                'currency'       => $toWallet->currency,
                'amount'         => $payload['amount'],
                'type'           => TransactionTypeEnum::CREDIT,
                'reference'      => $reference,
                'idempotencyKey' =>  $baseKey . ':credit',
                'meta' => [
                    'from_user' => auth()->id(),
                ],
            ]);

            /**
             * STEP 3: Finalize sender debit
             */
            $debitTx = $this->apply->execute([
                'walletId'       => $fromWallet->id,
                'currency'       => $fromWallet->currency,
                'amount'         => $payload['amount'],
                'type'           => TransactionTypeEnum::DEBIT,
                'reference'      => $reference,
                'idempotencyKey' => $baseKey . ':debit',
                'meta' => [
                    'to_user' => $toWallet->user_id,
                ],
            ]);

            return [
                'reference' => $reference,
                'amount'    => (float) $payload['amount'],
                'credit_tx' => $creditTx->id ?? null,
                'debit_tx'  => $debitTx->id ?? null,
            ];
        });
    }
}
