<?php

use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Wallet\actions\FailWithdrawalAction;
use Modules\Wallet\actions\ReserveFundsAction;
use Modules\Wallet\app\Interfaces\ReleaseReservedFunds;
use Modules\Wallet\app\Jobs\ReconcileWithdrawalsJob;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\Withdrawal;
use Modules\Wallet\tests\TestCase;

uses(TestCase::class);

test('it reconciles failed withdrawals and releases reserved funds', function () {
    $withdrawal = Withdrawal::factory()->create([
        'status'        => WithdrawalStatusEnum::FAILED,
        'is_reconciled' => false,
    ]);

    $release = Mockery::mock(ReleaseReservedFunds::class);
    $release->shouldReceive('execute')
        ->once()
        ->with(Mockery::on(fn ($payload) =>
            $payload['reference'] === $withdrawal->reference &&
            str_starts_with($payload['idempotencyKey'], 'reconcile:')
        ));

    app()->instance(ReleaseReservedFunds::class, $release);

    (new ReconcileWithdrawalsJob())->handle($release);

    $withdrawal->refresh();

    expect((bool) $withdrawal->is_reconciled)->toBeTrue();
    expect($withdrawal->reconciled_at)->not->toBeNull();
});

test('it skips already reconciled withdrawals', function () {
    Withdrawal::factory()->create([
        'status'        => WithdrawalStatusEnum::FAILED,
        'is_reconciled' => true,
    ]);

    $release = Mockery::mock(ReleaseReservedFunds::class);
    $release->shouldNotReceive('execute');

    app()->instance(ReleaseReservedFunds::class, $release);

    (new ReconcileWithdrawalsJob())->handle($release);

    expect(true)->toBeTrue();
});

test('it marks already released failures as reconciled', function () {
    $user = User::factory()->create();

    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'currency' => 'NGN',
        'available_balance' => '4000',
        'reserved_balance' => '0',
    ]);

    $reference = 'WD-' . Str::uuid();

    app(ReserveFundsAction::class)->execute([
        'walletId' => $wallet->id,
        'userId' => $user->id,
        'currency' => 'NGN',
        'amount' => '1500',
        'reference' => $reference,
        'idempotencyKey' => (string) Str::uuid(),
        'meta' => [
            'destination' => ['type' => 'bank_account'],
        ],
    ]);

    $withdrawal = Withdrawal::factory()->create([
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
        'amount' => '1500',
        'currency' => 'NGN',
        'reference' => $reference,
        'status' => WithdrawalStatusEnum::PROCESSING,
        'is_reconciled' => false,
    ]);

    app(FailWithdrawalAction::class)->execute($withdrawal, 'provider_error');

    (new ReconcileWithdrawalsJob())->handle(app(ReleaseReservedFunds::class));

    expect($withdrawal->fresh()->status)->toBe(WithdrawalStatusEnum::FAILED);
    expect((bool) $withdrawal->fresh()->is_reconciled)->toBeTrue();
    expect($withdrawal->fresh()->reconciled_at)->not->toBeNull();
    expect((float) $wallet->fresh()->available_balance)->toEqual(4000.0);
    expect((float) $wallet->fresh()->reserved_balance)->toEqual(0.0);
});
