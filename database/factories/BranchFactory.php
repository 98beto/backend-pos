<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'name' => $name,
            'code' => strtoupper($this->faker->unique()->bothify('BR-###')),
            'address' => $this->faker->address(),
            'is_active' => true,
        ];
    }
}
