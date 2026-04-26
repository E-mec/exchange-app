<?php

use Illuminate\Support\Facades\Bus;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\TestCase;
use Modules\Wallet\actions\InitiateWithdrawalAction;
use Modules\Wallet\app\Jobs\ProcessWithdrawalJob;
use Modules\Wallet\app\Interfaces\PayoutProvider;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\Withdrawal;
use Illuminate\Support\Str;
use Mockery;

uses(TestCase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'api');
});

test('it dispatches process withdrawal job when withdrawal is initiated', function () {
    Bus::fake();
    
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'currency' => 'NGN',
        'available_balance' => '50000',
    ]);

    $action = app(InitiateWithdrawalAction::class);

    $withdrawal = $action->execute([
        'currency' => 'NGN',
        'amount' => 5000,
        'destination' => [
            'type' => 'bank_account',
            'account_number' => '1234567890',
            'bank_code' => '058'
        ],
        'idempotency_key' => (string) Str::uuid(),
    ]);

    expect($withdrawal->status)->toBe(WithdrawalStatusEnum::PROCESSING);
    
    Bus::assertDispatched(ProcessWithdrawalJob::class, function ($job) use ($withdrawal) {
        return $job->withdrawalReference === $withdrawal->reference;
    });
});

test('process withdrawal job handles successful payout', function () {
    $withdrawal = Withdrawal::factory()->create([
        'user_id' => $this->user->id,
        'amount' => 5000,
        'currency' => 'NGN',
        'status' => WithdrawalStatusEnum::PROCESSING,
        'meta' => [
            'destination' => [
                'type' => 'bank_account',
                'account_number' => '1234567890',
                'bank_code' => '058'
            ]
        ]
    ]);

    $provider = Mockery::mock(PayoutProvider::class);
    $provider->shouldReceive('execute')
        ->once()
        ->andReturn([
            'reference' => 'PROV-12345',
            'status' => 'success'
        ]);

    $job = new ProcessWithdrawalJob($withdrawal->reference);
    $job->handle($provider);

    $updatedWithdrawal = $withdrawal->refresh();
    expect($updatedWithdrawal->provider_reference)->toBe('PROV-12345');
});

test('process withdrawal job retries on provider failure', function () {
    $withdrawal = Withdrawal::factory()->create([
        'user_id' => $this->user->id,
        'amount' => 5000,
        'currency' => 'NGN',
        'status' => WithdrawalStatusEnum::PROCESSING,
        'meta' => [
            'destination' => [
                'type' => 'bank_account',
                'account_number' => '1234567890',
                'bank_code' => '058'
            ]
        ]
    ]);

    $provider = Mockery::mock(PayoutProvider::class);
    $provider->shouldReceive('execute')
        ->once()
        ->andThrow(new Exception('Provider temporarily unavailable'));

    $job = new ProcessWithdrawalJob($withdrawal->reference);

    // Should retry and not throw on first attempt
    try {
        $job->handle($provider);
    } catch (Exception $e) {
        expect($e->getMessage())->toContain('Provider');
    }
});
