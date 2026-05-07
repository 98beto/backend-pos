<?php

namespace Database\Factories;

use App\Models\BranchProduct;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleDetail>
 */
class SaleDetailFactory extends Factory
{
    protected $model = SaleDetail::class;

    public function definition(): array
    {
        $product  = Product::inRandomOrder()->first() ?? Product::factory()->create();
        $branchProduct = BranchProduct::where('product_id', $product?->id)->inRandomOrder()->first();
        $quantity = $this->faker->numberBetween(1, 5);
        $price    = $branchProduct?->price ?? 100;
        $subtotal = round($quantity * $price, 2);
        $tax      = 0;
        $total    = $subtotal;

        return [
            'sale_id'    => Sale::inRandomOrder()->first()?->id,
            'product_id' => $product->id,
            'quantity'   => $quantity,
            'unit_price' => $price,
            'tax_amount' => $tax,
            'subtotal'   => $subtotal,
            'total'      => $total,
        ];
    }
}
