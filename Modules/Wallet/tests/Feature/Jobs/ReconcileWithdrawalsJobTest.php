<?php

use Modules\Wallet\app\Interfaces\ReleaseReservedFunds;
use Modules\Wallet\app\Jobs\ReconcileWithdrawalsJob;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Wallet\Models\Withdrawal;
use Modules\Wallet\tests\TestCase;

uses(TestCase::class);

test('it reconciles failed withdrawals and releases reserved funds', function () {
    // Arrange
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

    // Act
    (new ReconcileWithdrawalsJob())->handle($release);

    // Assert
    $withdrawal->refresh();

    expect((boolean) $withdrawal->is_reconciled)->toBeTrue();
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

    expect(true)->toBeTrue(); // nothing exploded = pass
});
