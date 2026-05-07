<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\InventoryMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryMovementShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_requested_inventory_movement_for_the_authenticated_branch(): void
    {
        $branch = Branch::factory()->create();
        $this->actingAsDevice($branch);

        $product = $this->createProductInBranch($branch, [
            'name' => 'Producto de prueba',
            'sku' => 'INV-001',
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
            ->assertJsonPath('data.product.id', $product->id)
            ->assertJsonPath('data.product.name', 'Producto de prueba');
    }

    public function test_it_returns_404_for_movements_from_another_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $this->actingAsDevice($branchA);

        $product = $this->createProductInBranch($branchB, ['sku' => 'INV-002']);
        $movement = InventoryMovement::factory()->create([
            'product_id' => $product->id,
            'branch_id' => $branchB->id,
        ]);

        $this->getJson("/api/inventory/movements/{$movement->id}")->assertNotFound();
    }

    public function test_it_lists_only_movements_for_the_authenticated_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $this->actingAsDevice($branchA);

        $productA = $this->createProductInBranch($branchA, ['sku' => 'INV-003']);
        $productB = $this->createProductInBranch($branchB, ['sku' => 'INV-004']);

        $expected = InventoryMovement::factory()->create([
            'product_id' => $productA->id,
            'branch_id' => $branchA->id,
        ]);

        InventoryMovement::factory()->create([
            'product_id' => $productB->id,
            'branch_id' => $branchB->id,
        ]);

        $response = $this->getJson('/api/inventory/movements');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $expected->id)
            ->assertJsonPath('data.data.0.branch_id', $branchA->id);
    }

    public function test_it_stores_branch_id_on_manual_inventory_movements_from_device_context(): void
    {
        $branch = Branch::factory()->create();
        $this->actingAsDevice($branch);
        $product = $this->createProductInBranch($branch, ['sku' => 'INV-005'], [
            'stock_quantity' => 10,
        ]);

        $response = $this->postJson('/api/inventory/movements', [
            'product_id' => $product->id,
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
