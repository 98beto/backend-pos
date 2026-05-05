<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\SavedCart;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedCart>
 */
class SavedCartFactory extends Factory
{
    protected $model = SavedCart::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'customer_id' => null,
            'cash_session_id' => null,
            'branch_id' => Branch::factory(),
            'discount_amount' => 0,
            'status' => 'saved',
            'notes' => null,
        ];
    }
}
