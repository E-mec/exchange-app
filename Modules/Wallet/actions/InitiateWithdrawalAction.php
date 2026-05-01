<?php

namespace Modules\Wallet\actions;

use App\Actions\VerifyPin;
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

    /**
     * @throws CustomException
     */

    public function execute(array $payload): Withdrawal
    {
        app(VerifyPin::class)->execute($payload['pin']);
        // Phase 1: DB work only — reserve + create withdrawal
        $withdrawal = DB::transaction(function () use ($payload) {

            $wallet = Wallet::forUser(auth()->id(), $payload['currency']);

            if (! $wallet) {
                throw new CustomException('Wallet not found');
            }

            $baseKey               = $payload['idempotency_key'];
            $reference             = 'WD-' . Str::uuid();
            $reserveIdempotencyKey = $baseKey . ':reserve';

            // Idempotency check
            $existingReserve = WalletTransaction::query()
                ->where('wallet_id', $wallet->id)
                ->where('type', TransactionTypeEnum::RESERVE)
                ->where('idempotency_key', $reserveIdempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existingReserve) {
                // Already reserved — return the existing withdrawal
                $withdrawal = Withdrawal::query()
                    ->where('reference', $existingReserve->reference)
                    ->lockForUpdate()
                    ->firstOrFail();

                return $withdrawal; // status may be PENDING or PROCESSING
            }

            // Reserve funds
            $this->reserve->execute([
                'walletId'       => $wallet->id,
                'userId'         => auth()->id(),
                'currency'       => $wallet->currency,
                'amount'         => $payload['amount'],
                'reference'      => $reference,
                'idempotencyKey' => $reserveIdempotencyKey,
                'meta'           => ['destination' => $payload['destination']],
            ]);

            // Create withdrawal record — stays PENDING until job runs
            return Withdrawal::create([
                'wallet_id' => $wallet->id,
                'user_id'   => auth()->id(),
                'amount'    => $payload['amount'],
                'currency'  => $wallet->currency,
                'reference' => $reference,
                'status'    => WithdrawalStatusEnum::PENDING,
                'meta'      => ['destination' => $payload['destination']],
            ]);

        }); // ← Transaction COMMITS here. Row exists in DB.

        // Phase 2: If still pending, mark PROCESSING and dispatch job
        // Safe to call now — transaction is committed, row is visible
        if ($withdrawal->status === WithdrawalStatusEnum::PENDING) {
            $withdrawal = $this->process->execute($withdrawal);
        }

        return $withdrawal->refresh()->load(['user', 'wallet']);
    }
}
