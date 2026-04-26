<?php

namespace Modules\Wallet\actions;

use App\Exceptions\CustomException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Wallet\app\Interfaces\ReserveFunds;
use Modules\Wallet\enums\TransactionTypeEnum;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\Withdrawal;

final class InitiateWithdrawalAction
{
    public function __construct(
        protected ReserveFunds $reserve,
        protected ProcessWithdrawalAction $process,
    ) {}

    public function execute(array $payload): Withdrawal
    {
        return DB::transaction(/**
         * @throws CustomException
         */ function () use ($payload) {

            $wallet = Wallet::forUser(
                auth()->id(),
                $payload['currency']
            );

            if (! $wallet) {
                throw new CustomException('Wallet not found');
            }

            $baseKey   = $payload['idempotency_key'];
            $reference = 'WD-' . Str::uuid();
            $reserveIdempotencyKey = $baseKey . ':reserve';

            $existingReserve = WalletTransaction::query()
                ->where('wallet_id', $wallet->id)
                ->where('type', TransactionTypeEnum::RESERVE)
                ->where('idempotency_key', $reserveIdempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existingReserve) {
                $withdrawal = Withdrawal::query()
                    ->where('reference', $existingReserve->reference)
                    ->lockForUpdate()
                    ->first();

                if (! $withdrawal) {
                    throw new CustomException('Reserved withdrawal record not found');
                }

                if ($withdrawal->status === WithdrawalStatusEnum::PENDING) {
                    $withdrawal = $this->process->execute($withdrawal);
                }

                return $withdrawal->refresh()->load(['user', 'wallet']);
            }

            /**
             * STEP 1: Reserve funds
             */
            $this->reserve->execute([
                'walletId'       => $wallet->id,
                'userId'         => auth()->id(),
                'currency'       => $wallet->currency,
                'amount'         => $payload['amount'],
                'reference'      => $reference,
                'idempotencyKey' => $reserveIdempotencyKey,
                'meta' => [
                    'destination' => $payload['destination'],
                ],
            ]);

            /**
             * STEP 2: Create withdrawal record
             */
            $withdrawal = Withdrawal::create([
                'wallet_id' => $wallet->id,
                'user_id'   => auth()->id(),
                'amount'    => $payload['amount'],
                'currency'  => $wallet->currency,
                'reference' => $reference,
                'status'    => WithdrawalStatusEnum::PENDING,
                'meta' => [
                    'destination' => $payload['destination'],
                ],
            ]);

            $withdrawal = $this->process->execute($withdrawal);

            return $withdrawal->refresh()->load(['user', 'wallet']);

        });
    }
}
