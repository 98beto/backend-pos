<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    protected $model = InventoryMovement::class;

    private static array $notes = [
        'in'         => ['Compra OC-001', 'Compra OC-002', 'Reposición de stock', 'Entrada por compra directa', 'Mercancía recibida'],
        'out'        => ['Merma registrada', 'Devolución a proveedor', 'Producto dañado', 'Salida por préstamo'],
        'adjustment' => ['Conteo físico', 'Ajuste de inventario mensual', 'Corrección de diferencia', 'Auditoría de stock'],
    ];

    public function definition(): array
    {
        $type = $this->faker->randomElement(['in', 'in', 'in', 'out', 'adjustment']);

        return [
            'product_id' => Product::inRandomOrder()->first()?->id,
            'branch_id' => Branch::factory(),
            'type'       => $type,
            'quantity'   => $type === 'adjustment'
                ? $this->faker->numberBetween(0, 100)
                : $this->faker->numberBetween(1, 50),
            'source'     => 'manual',
            'reference_id' => null,
            'notes'      => $this->faker->randomElement(static::$notes[$type]),
        ];
    }
}
