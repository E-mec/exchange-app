<?php

use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Wallet\actions\TransferFundsAction;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\tests\TestCase;

uses(TestCase::class);

test('transfers funds between wallets', function () {

    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $senderWallet = Wallet::factory()->create([
        'user_id' => $sender->id,
        'currency' => 'NGN',
        'available_balance' => '10000',
        'reserved_balance' => '0',
    ]);

    $receiverWallet = Wallet::factory()->create([
        'user_id' => $receiver->id,
        'currency' => 'NGN',
        'available_balance' => '0',
    ]);

    $this->actingAs($sender, 'api');

   app(TransferFundsAction::class)->execute([
        'from_currency' => 'NGN',
        'to_user_id' => $receiver->id,
        'to_currency' => 'NGN',
        'amount' => '5000',
        'idempotency_key' => (string)Str::uuid(),
    ]);

    expect((float) $senderWallet->refresh()->available_balance)->toEqual(5000)
        ->and((float) $receiverWallet->refresh()->available_balance)->toEqual(5000)
        ->and(WalletTransaction::count())->toBe(3);

    // reserve + credit + debit
});

test('fails if balance is insufficient', function () {

    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $wallet = Wallet::factory()->create([
        'user_id' => $sender->id,
        'currency' => 'NGN',
        'available_balance' => '1000',
    ]);

    Wallet::factory()->create([
        'user_id' => $receiver->id,
        'currency' => 'NGN',
        'available_balance' => '0',
    ]);

    $this->actingAs($sender, 'api');

    expect(fn () =>
    app(TransferFundsAction::class)->execute([
        'from_currency' => 'NGN',
        'to_user_id' => $receiver->id,
        'to_currency' => 'NGN',
        'amount' => '5000',
        'idempotency_key' => (string) Str::uuid(),
    ])
    )->toThrow(\App\Exceptions\CustomException::class);

    expect((float) $wallet->refresh()->available_balance)->toEqual('1000');
    expect( WalletTransaction::count())->toBe(0);
});

test('is idempotent', function () {

    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $senderWallet = Wallet::factory()->create([
        'user_id' => $sender->id,
        'currency' => 'NGN',
        'available_balance' => '10000',
    ]);

    $receiverWallet = Wallet::factory()->create([
        'user_id' => $receiver->id,
        'currency' => 'NGN',
    ]);

    $this->actingAs($sender, 'api');

    $key = (string) Str::uuid();

    app(TransferFundsAction::class)->execute([
        'from_currency' => 'NGN',
        'to_user_id' => $receiver->id,
        'to_currency' => 'NGN',
        'amount' => '3000',
        'idempotency_key' => $key,
    ]);

    app(TransferFundsAction::class)->execute([
        'from_currency' => 'NGN',
        'to_user_id' => $receiver->id,
        'to_currency' => 'NGN',
        'amount' => '3000',
        'idempotency_key' => $key,
    ]);

    expect((float) $senderWallet->refresh()->available_balance)->toEqual('7000');
    expect((float) $receiverWallet->refresh()->available_balance)->toEqual('3000');

    expect(
        WalletTransaction::where('idempotency_key', $key)->count()
    )->toBe(0);

    // AFTER — actually checks the transfer ran exactly once
    expect((float) $senderWallet->refresh()->available_balance)->toEqual(7000.0);
    expect((float) $receiverWallet->refresh()->available_balance)->toEqual(3000.0);
// Confirm exactly 3 transactions were created (reserve + credit + debit), not 6
    expect(WalletTransaction::count())->toBe(3);
});

test('does not double debit on retry', function () {

    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $senderWallet = Wallet::factory()->create([
        'user_id' => $sender->id,
        'currency' => 'NGN',
        'available_balance' => '8000',
    ]);

    Wallet::factory()->create([
        'user_id' => $receiver->id,
        'currency' => 'NGN',
    ]);

    $this->actingAs($sender, 'api');

    $key = (string) Str::uuid();

    for ($i = 0; $i < 3; $i++) {
        app(TransferFundsAction::class)->execute([
            'from_currency' => 'NGN',
            'to_user_id' => $receiver->id,
            'to_currency' => 'NGN',
            'amount' => '2000',
            'idempotency_key' => $key,
        ]);
    }

    expect((float) $senderWallet->refresh()->available_balance)->toEqual('6000');
});

test('rolls back if credit fails', function () {

    $sender = User::factory()->create();

    $senderWallet = Wallet::factory()->create([
        'user_id' => $sender->id,
        'currency' => 'NGN',
        'available_balance' => '5000',
    ]);

    $this->actingAs($sender, 'api');

    // Force failure by using non-existent destination wallet
    expect(fn () =>
    app(TransferFundsAction::class)->execute([
        'from_currency' => 'NGN',
        'to_user_id' => 999999,
        'to_currency' => 'NGN',
        'amount' => '3000',
        'idempotency_key' => (string) Str::uuid(),
    ])
    )->toThrow(\App\Exceptions\CustomException::class);

    expect((float) $senderWallet->refresh()->available_balance)->toEqual('5000');
    expect((float) $senderWallet->reserved_balance)->toEqual('0');

    expect(WalletTransaction::count())->toBe(0);
});
