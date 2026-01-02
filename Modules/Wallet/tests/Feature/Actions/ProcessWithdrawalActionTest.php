<?php

use Modules\Wallet\actions\ProcessWithdrawalAction;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Wallet\Models\Withdrawal;
use App\Exceptions\CustomException;
use Modules\Wallet\tests\TestCase;

uses(TestCase::class);

test('it processes a pending withdrawal', function () {
    $withdrawal = Withdrawal::factory()->create([
        'status' => WithdrawalStatusEnum::PENDING,
    ]);

    $action = new ProcessWithdrawalAction();

    $result = $action->execute($withdrawal);

    expect($result->status)->toBe(WithdrawalStatusEnum::PROCESSING);
});

test('it rejects non-pending withdrawals', function () {
    $withdrawal = Withdrawal::factory()->create([
        'status' => WithdrawalStatusEnum::SUCCESS,
    ]);

    $action = new ProcessWithdrawalAction();

    expect(fn () => $action->execute($withdrawal))
        ->toThrow(CustomException::class, 'Withdrawal not in pending state');
});
