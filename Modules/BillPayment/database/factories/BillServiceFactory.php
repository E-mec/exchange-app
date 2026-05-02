<?php

namespace Modules\BillPayment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BillServiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\BillPayment\Models\BillService::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

