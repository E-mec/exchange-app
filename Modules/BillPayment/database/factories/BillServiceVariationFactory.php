<?php

namespace Modules\BillPayment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BillServiceVariationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\BillPayment\Models\BillServiceVariation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

