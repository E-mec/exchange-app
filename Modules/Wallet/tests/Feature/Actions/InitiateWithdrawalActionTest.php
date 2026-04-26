<?php

use App\Exceptions\CustomException;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\TestCase;
use Modules\Wallet\actions\InitiateWithdrawalAction;
use Modules\Wallet\actions\ProcessWithdrawalAction;
use Modules\Wallet\app\Interfaces\ReserveFunds;
use Modules\Wallet\enums\TransactionTypeEnum;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\Withdrawal;

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

    $reserve = Mockery::mock(ReserveFunds::class);
    $reserve->shouldReceive('execute')
        ->once()
        ->withArgs(function ($payload) use ($wallet) {
            expect($payload['walletId'])->toBe($wallet->id);
            expect((float) $payload['amount'])->toEqual(5000);
            expect(Str::startsWith($payload['reference'], 'WD-'))->toBeTrue();

            return true;
        });

    $action = new InitiateWithdrawalAction($reserve, new ProcessWithdrawalAction());

    $withdrawal = $action->execute([
        'currency' => 'NGN',
        'amount' => 5000,
        'destination' => ['type' => 'bank_account'],
        'idempotency_key' => (string) Str::uuid(),
    ]);

    expect($withdrawal)
        ->wallet_id->toBe($wallet->id)
        ->user_id->toBe($this->user->id)
        ->amount->toEqual(5000)
        ->status->toBe(WithdrawalStatusEnum::PROCESSING);

    expect($withdrawal->meta['destination']['type'])->toBe('bank_account');
});

test('it returns the existing withdrawal on idempotent retry', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'currency' => 'NGN',
        'available_balance' => '10000',
    ]);

    $idempotencyKey = (string) Str::uuid();
    $action = app(InitiateWithdrawalAction::class);

    $payload = [
        'currency' => 'NGN',
        'amount' => 5000,
        'destination' => ['type' => 'bank_account'],
        'idempotency_key' => $idempotencyKey,
    ];

    $firstWithdrawal = $action->execute($payload);
    $secondWithdrawal = $action->execute($payload);

    expect($firstWithdrawal->id)->toBe($secondWithdrawal->id);
    expect(Withdrawal::query()->count())->toBe(1);
    expect(
        WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('type', TransactionTypeEnum::RESERVE)
            ->count()
    )->toBe(1);
});

test('it throws exception when wallet is missing', function () {
    $reserve = Mockery::mock(ReserveFunds::class);
    $action = new InitiateWithdrawalAction($reserve, new ProcessWithdrawalAction());

    expect(fn () => $action->execute([
        'currency' => 'NGN',
        'amount' => 1000,
        'destination' => ['type' => 'bank'],
        'idempotency_key' => (string) Str::uuid(),
    ]))->toThrow(CustomException::class, 'Wallet not found');
});
