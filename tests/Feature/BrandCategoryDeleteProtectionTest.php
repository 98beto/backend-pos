<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandCategoryDeleteProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_deleting_a_category_with_associated_products(): void
    {
        $this->actingAsDevice(Branch::factory()->create());
        $category = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete category with associated products.');
    }

    public function test_it_rejects_deleting_a_brand_with_associated_products(): void
    {
        $this->actingAsDevice(Branch::factory()->create());
        $brand = Brand::factory()->create();

        Product::factory()->create([
            'brand_id' => $brand->id,
        ]);

        $response = $this->deleteJson("/api/brands/{$brand->id}");

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete brand with associated products.');
    }
}
