<?php

namespace Modules\Wallet\actions;

use App\Exceptions\CustomException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Wallet\enums\TransactionTypeEnum;
use Modules\Wallet\enums\WalletStatusEnum;
use Modules\Wallet\Models\WalletTransaction;

final class ApplyTransactionOrchestratorAction
{
    public function __construct(
        protected WalletLockAction        $walletLock,
        protected IdempotencyCheckAction  $idempotencyChecker,
        protected BalanceCalculatorAction $calculator,
        protected UpdateWalletBalanceAction $updater,
        protected GenerateLedgerHashAction  $hasher,
        protected WriteLedgerEntryAction    $writer,
    ) {}

    public function execute(array $payload): WalletTransaction
    {
        return DB::transaction(function () use ($payload) {

            $payload['idempotencyKey'] = $payload['idempotencyKey'] ?? Str::uuid()->toString();

            // lock wallet
            $wallet = $this->walletLock->execute($payload['walletId']);

            if ($wallet->status !== WalletStatusEnum::ACTIVE) {
                throw new CustomException('Wallet is not active.');
            }

            // idempotency check
            $existing = $this->idempotencyChecker->execute($payload['idempotencyKey']);
            if ($existing) {
                return $existing;
            }

            // compute balances
            $before = (string) $wallet->available_balance;
            $calc = $this->calculator->execute(
                available: (string) $wallet->available_balance,
                reserved:  (string) $wallet->reserved_balance,
                amount:    $payload['amount'],
                type:      $payload['type']
            );
            $afterAvailable = $calc['available'];
            $afterReserved  = $calc['reserved'];

            // update wallet balances
            $this->updater->execute(
                wallet: $wallet,
                available: $afterAvailable,
                reserved: $afterReserved
            );

            // prepare ledger row
            $ledgerRow = [
                'wallet_id'       => $wallet->id,
                'user_id'         => $wallet->user_id,
                'currency'        => $payload['currency'],
                'type'            => $payload['type'],
                'before_balance'  => $before,
                'amount'          => $payload['amount'],
                'balance_after'   => $afterAvailable,
                'reference'       => $payload['reference'],
                'idempotency_key' => $payload['idempotencyKey'],
                'meta'            => $payload['meta'],
            ];

            // fetch last txn for previous_hash
            $last = WalletTransaction::where('wallet_id', $wallet->id)->latest('id')->first();
            $previousHash = $last?->current_hash;

            // generate hashes
            $hashes = $this->hasher->execute($ledgerRow, $previousHash);

            $ledgerRow = array_merge($ledgerRow, $hashes);

            return $this->writer->execute($ledgerRow);
        });
    }
}
