<?php

namespace Modules\Wallet\actions;

use App\Exceptions\CustomException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Wallet\app\Interfaces\ReserveFunds;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\Withdrawal;

final class InitiateWithdrawalAction
{
    public function __construct(
        protected ReserveFunds $reserve,
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

            $reference = 'WD-' . Str::uuid();
            $baseKey   = $payload['idempotency_key'];

            /**
             * STEP 1: Reserve funds
             */
            $this->reserve->execute([
                'walletId'       => $wallet->id,
                'userId'         => auth()->id(),
                'currency'       => $wallet->currency,
                'amount'         => $payload['amount'],
                'reference'      => $reference,
                'idempotencyKey' => $baseKey . ':reserve',
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
            return $withdrawal->refresh()->load(['user', 'wallet']);
        });
    }
}
