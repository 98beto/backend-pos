<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->unique()->randomElement([
                'Stanley',
                'Truper',
                'Irwin',
                'Bosch',
                'DeWalt',
                'Makita',
                '3M',
                'Urrea',
                'Pretul',
                'Surtek',
            ]),
            'description' => $this->faker->optional()->sentence(),
            'img_url'     => null,
        ];
    }
}
