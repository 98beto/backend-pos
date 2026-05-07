<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BranchProduct>
 */
class BranchProductFactory extends Factory
{
    protected $model = BranchProduct::class;

    public function definition(): array
    {
        $product = Product::factory()->create();

        return [
            'branch_id' => Branch::factory(),
            'product_id' => $product->id,
            'price' => $this->faker->randomFloat(2, 5, 500),
            'stock_quantity' => $this->faker->numberBetween(0, 80),
            'min_stock' => $this->faker->numberBetween(0, 10),
            'is_available' => true,
        ];
    }
}
