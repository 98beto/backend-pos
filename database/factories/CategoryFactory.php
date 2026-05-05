<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->unique()->randomElement([
                'Herramientas Manuales',
                'Herramientas Eléctricas',
                'Plomería',
                'Electricidad',
                'Pinturas y Acabados',
                'Construcción',
                'Jardinería',
                'Tornillería y Fijaciones',
                'Seguridad Industrial',
                'Adhesivos y Selladores',
            ]),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
