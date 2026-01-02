<?php

namespace Modules\Wallet\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\Withdrawal;

class WithdrawalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Withdrawal::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $user   = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id'  => $user->id,
            'currency' => 'NGN',
        ]);

        return [
            'wallet_id' => $wallet->id,
            'user_id'   => $user->id,
            'amount'    => 5000,
            'currency'  => $wallet->currency,
            'reference' => 'WD-' . Str::uuid(),
            'status'    => WithdrawalStatusEnum::PENDING,
            'failure_reason' => null,
            'meta' => [
                'destination' => 'bank_account',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | States
    |--------------------------------------------------------------------------
    */

    public function pending(): static
    {
        return $this->state([
            'status' => WithdrawalStatusEnum::PENDING,
        ]);
    }

    public function processing(): static
    {
        return $this->state([
            'status' => WithdrawalStatusEnum::PROCESSING,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => WithdrawalStatusEnum::FAILED,
            'failure_reason' => 'provider_error',
        ]);
    }

    public function success(): static
    {
        return $this->state([
            'status' => WithdrawalStatusEnum::SUCCESS,
        ]);
    }
}

