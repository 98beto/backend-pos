<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashMovement>
 */
class CashMovementFactory extends Factory
{
    protected $model = CashMovement::class;

    public function definition(): array
    {
        return [
            'cash_session_id' => CashSession::factory()->open(),
            'branch_id' => Branch::factory(),
            'type' => $this->faker->randomElement(['in', 'out']),
            'category' => $this->faker->randomElement(['withdrawal', 'change', 'expense', 'refund', 'adjustment']),
            'amount' => $this->faker->randomFloat(2, 1, 500),
            'source' => 'manual',
            'reference_id' => null,
            'notes' => $this->faker->sentence(),
        ];
    }
}
