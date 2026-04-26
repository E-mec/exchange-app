<?php

use App\Exceptions\CustomException;
use Modules\Wallet\actions\FailWithdrawalAction;
use Modules\Wallet\app\Interfaces\ReleaseReservedFunds;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Wallet\Models\Withdrawal;
use Modules\Wallet\tests\TestCase;

uses(TestCase::class);

test('it fails a withdrawal and releases funds', function () {
    $withdrawal = Withdrawal::factory()->create([
        'status' => WithdrawalStatusEnum::PENDING,
    ]);

    $release = Mockery::mock(ReleaseReservedFunds::class);
    $release->shouldReceive('execute')
        ->once()
        ->withArgs(fn ($payload) =>
            $payload['reference'] === $withdrawal->reference
        );

    $action = new FailWithdrawalAction($release);

    $action->execute($withdrawal, 'provider_error');

    $withdrawal->refresh();

    expect($withdrawal->status)->toBe(WithdrawalStatusEnum::FAILED);
    expect($withdrawal->failure_reason)->toBe('provider_error');
});

test('fail withdrawal is idempotent', function () {
    $withdrawal = Withdrawal::factory()->create([
        'status' => WithdrawalStatusEnum::FAILED,
    ]);

    $release = Mockery::mock(ReleaseReservedFunds::class);
    $release->shouldNotReceive('execute');

    $action = new FailWithdrawalAction($release);

    $action->execute($withdrawal);

    expect($withdrawal->refresh()->status)->toBe(WithdrawalStatusEnum::FAILED);
});

test('it rejects failing a successful withdrawal', function () {
    $withdrawal = Withdrawal::factory()->create([
        'status' => WithdrawalStatusEnum::SUCCESS,
    ]);

    $release = Mockery::mock(ReleaseReservedFunds::class);
    $release->shouldNotReceive('execute');

    $action = new FailWithdrawalAction($release);

    expect(fn () => $action->execute($withdrawal))
        ->toThrow(CustomException::class, 'Successful withdrawals cannot be failed');
});
