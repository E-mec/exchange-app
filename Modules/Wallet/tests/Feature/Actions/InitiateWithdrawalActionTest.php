<?php

use Modules\Auth\Tests\TestCase;
use Modules\Wallet\actions\InitiateWithdrawalAction;
use Modules\Wallet\actions\ReserveFundsAction;
use Modules\Wallet\app\Interfaces\ReserveFunds;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Auth\Models\User;
use Modules\Wallet\Models\Wallet;
use App\Exceptions\CustomException;
use Illuminate\Support\Str;

uses(TestCase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'api');
});

test('it initiates a withdrawal successfully', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'currency' => 'NGN',
    ]);

// 🔒 Mock reserve action
    $reserve = Mockery::mock(ReserveFunds::class);
    $reserve->shouldReceive('execute')
        ->once()
        ->withArgs(function ($payload) use ($wallet) {
            expect($payload['walletId'])->toBe($wallet->id);
            expect((float) $payload['amount'])->toEqual(5000);
            expect(Str::startsWith($payload['reference'], 'WD-'))->toBeTrue();
            return true;
        });

    $action = new InitiateWithdrawalAction($reserve);

    $withdrawal = $action->execute([
        'currency' => 'NGN',
        'amount' => 5000,
        'destination' => 'bank_account',
        'idempotency_key' => 'test-key',
    ]);

    expect($withdrawal)
        ->wallet_id->toBe($wallet->id)
        ->user_id->toBe($this->user->id)
        ->amount->toEqual(5000)
        ->status->toBe(WithdrawalStatusEnum::PENDING);

    expect($withdrawal->meta['destination'])->toBe('bank_account');
});

test('it throws exception when wallet is missing', function () {
    $reserve = Mockery::mock(ReserveFunds::class);
    $action = new InitiateWithdrawalAction($reserve);

    expect(fn() => $action->execute([
        'currency' => 'NGN',
        'amount' => 1000,
        'destination' => 'bank',
        'idempotency_key' => 'test-key',
    ]))->toThrow(CustomException::class, 'Wallet not found');
});
