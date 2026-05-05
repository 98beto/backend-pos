<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SavedCart;
use App\Models\SavedCartItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedCartItem>
 */
class SavedCartItemFactory extends Factory
{
    protected $model = SavedCartItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 5);
        $unitPrice = 20;
        $subtotal = $quantity * $unitPrice;

        return [
            'saved_cart_id' => SavedCart::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_amount' => 0,
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ];
    }
}
