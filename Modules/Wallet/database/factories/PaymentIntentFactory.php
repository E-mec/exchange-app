<?php

namespace Modules\Wallet\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentIntentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Wallet\Models\PaymentIntent::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

