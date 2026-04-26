<?php

use App\Exceptions\CustomException;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Wallet\actions\FinalizeWithdrawalAction;
use Modules\Wallet\actions\ReserveFundsAction;
use Modules\Wallet\enums\TransactionTypeEnum;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\Withdrawal;
use Modules\Wallet\tests\TestCase;

uses(TestCase::class);

test('it finalizes a processing withdrawal and settles the reserve', function () {
    $user = User::factory()->create();

    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'currency' => 'NGN',
        'available_balance' => '10000',
        'reserved_balance' => '0',
    ]);

    $reference = 'WD-' . Str::uuid();

    app(ReserveFundsAction::class)->execute([
        'walletId' => $wallet->id,
        'userId' => $user->id,
        'currency' => 'NGN',
        'amount' => '2500',
        'reference' => $reference,
        'idempotencyKey' => (string) Str::uuid(),
        'meta' => [
            'destination' => ['type' => 'bank_account'],
        ],
    ]);

    $withdrawal = Withdrawal::factory()->create([
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
        'amount' => '2500',
        'currency' => 'NGN',
        'reference' => $reference,
        'status' => WithdrawalStatusEnum::PROCESSING,
    ]);

    app(FinalizeWithdrawalAction::class)->execute($withdrawal);

    $reserveTxn = WalletTransaction::query()
        ->where('wallet_id', $wallet->id)
        ->where('reference', $reference)
        ->where('type', TransactionTypeEnum::RESERVE)
        ->firstOrFail();

    $debitTxn = WalletTransaction::query()
        ->where('wallet_id', $wallet->id)
        ->where('reference', $reference)
        ->where('type', TransactionTypeEnum::DEBIT)
        ->first();

    expect($withdrawal->fresh()->status)->toBe(WithdrawalStatusEnum::SUCCESS);
    expect((float) $wallet->fresh()->available_balance)->toEqual(7500.0);
    expect((float) $wallet->fresh()->reserved_balance)->toEqual(0.0);
    expect($reserveTxn->fresh()->is_finalized)->toBeTrue();
    expect($reserveTxn->fresh()->finalized_at)->not->toBeNull();
    expect($debitTxn)->not->toBeNull();
});

test('it rejects finalizing a failed withdrawal', function () {
    $withdrawal = Withdrawal::factory()->failed()->create();

    expect(fn () => app(FinalizeWithdrawalAction::class)->execute($withdrawal))
        ->toThrow(CustomException::class, 'Failed withdrawals cannot be finalized');
});
