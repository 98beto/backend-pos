<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryMovementShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_requested_inventory_movement(): void
    {
        $branch = Branch::factory()->create();

        $product = Product::factory()->create([
            'name' => 'Producto de prueba',
        ]);

        $movement = InventoryMovement::factory()->create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'type' => 'in',
            'quantity' => 7,
            'notes' => 'Carga inicial',
        ]);

        $response = $this->getJson("/api/inventory/movements/{$movement->id}");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $movement->id)
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.branch_id', $branch->id)
            ->assertJsonPath('data.branch.id', $branch->id)
            ->assertJsonPath('data.branch.name', $branch->name)
            ->assertJsonPath('data.type', 'in')
            ->assertJsonPath('data.quantity', 7)
            ->assertJsonPath('data.notes', 'Carga inicial')
            ->assertJsonPath('data.product.id', $product->id)
            ->assertJsonPath('data.product.name', 'Producto de prueba');
    }

    public function test_it_returns_404_when_inventory_movement_does_not_exist(): void
    {
        $response = $this->getJson('/api/inventory/movements/999999');

        $response
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Record not found.',
            ]);
    }

    public function test_it_filters_inventory_movements_by_branch_id(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $product = Product::factory()->create();

        $expected = InventoryMovement::factory()->create([
            'product_id' => $product->id,
            'branch_id' => $branchA->id,
        ]);

        InventoryMovement::factory()->create([
            'product_id' => $product->id,
            'branch_id' => $branchB->id,
        ]);

        $response = $this->getJson("/api/inventory/movements?branch_id={$branchA->id}");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $expected->id)
            ->assertJsonPath('data.data.0.branch_id', $branchA->id)
            ->assertJsonPath('data.data.0.branch.id', $branchA->id);
    }

    public function test_it_stores_branch_id_on_manual_inventory_movements(): void
    {
        $branch = Branch::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 10,
        ]);

        $response = $this->postJson('/api/inventory/movements', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'type' => 'in',
            'quantity' => 5,
            'notes' => 'Entrada manual',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.branch_id', $branch->id)
            ->assertJsonPath('data.branch.id', $branch->id);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'type' => 'in',
            'quantity' => 5,
        ]);
    }
}
