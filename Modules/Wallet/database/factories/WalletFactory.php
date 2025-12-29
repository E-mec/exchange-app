<?php

namespace Modules\Wallet\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Wallet\Models\Wallet;

class WalletFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Wallet::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'currency' => fake()->randomElement(['NGN', 'BTC', 'USDT']),
            'available_balance' => 0,
            'reserved_balance' => 0,
            'ledger_balance' => 0,
            'meta' => [],
            'status' => 'active',
        ];
    }
}

