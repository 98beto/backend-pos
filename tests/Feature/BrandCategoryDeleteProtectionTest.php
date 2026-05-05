<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandCategoryDeleteProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_deleting_a_category_with_associated_products(): void
    {
        $category = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete category with associated products.');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_it_rejects_deleting_a_brand_with_associated_products(): void
    {
        $brand = Brand::factory()->create();

        Product::factory()->create([
            'brand_id' => $brand->id,
        ]);

        $response = $this->deleteJson("/api/brands/{$brand->id}");

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete brand with associated products.');

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
        ]);
    }
}
