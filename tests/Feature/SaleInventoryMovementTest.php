<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashSession;
use App\Models\CashMovement;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleInventoryMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_decrements_stock_and_creates_inventory_movements(): void
    {
        $branch = Branch::factory()->create();

        $cashSession = CashSession::factory()->open()->create([
            'branch_id' => $branch->id,
            'device_identifier' => 'POS-01',
        ]);

        $productA = Product::factory()->create([
            'stock_quantity' => 10,
            'price' => 20,
        ]);

        $productB = Product::factory()->create([
            'stock_quantity' => 8,
            'price' => 15,
        ]);

        $response = $this->postJson('/api/sales', [
            'branch_id' => $branch->id,
            'cash_session_id' => $cashSession->id,
            'payment_method' => 'cash',
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 2,
                    'unit_price' => 20,
                    'tax_amount' => 0,
                    'subtotal' => 40,
                    'total' => 40,
                ],
                [
                    'product_id' => $productB->id,
                    'quantity' => 3,
                    'unit_price' => 15,
                    'tax_amount' => 0,
                    'subtotal' => 45,
                    'total' => 45,
                ],
            ],
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $response
            ->assertJsonPath('data.branch_id', $branch->id)
            ->assertJsonPath('data.branch.id', $branch->id)
            ->assertJsonPath('data.branch.name', $branch->name);

        $saleId = $response->json('data.id');

        $this->assertDatabaseHas('products', [
            'id' => $productA->id,
            'stock_quantity' => 8,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $productB->id,
            'stock_quantity' => 5,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $productA->id,
            'type' => 'out',
            'quantity' => 2,
            'source' => 'sale',
            'reference_id' => $saleId,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $productB->id,
            'type' => 'out',
            'quantity' => 3,
            'source' => 'sale',
            'reference_id' => $saleId,
        ]);

        $this->assertDatabaseHas('cash_movements', [
            'cash_session_id' => $cashSession->id,
            'branch_id' => $branch->id,
            'type' => 'in',
            'category' => 'sale',
            'source' => 'sale',
            'reference_id' => $saleId,
            'amount' => '85.00',
        ]);
    }

    public function test_failed_sale_does_not_create_inventory_movements(): void
    {
        $branch = Branch::factory()->create();

        $cashSession = CashSession::factory()->open()->create([
            'branch_id' => $branch->id,
            'device_identifier' => 'POS-01',
        ]);

        $product = Product::factory()->create([
            'stock_quantity' => 1,
            'price' => 20,
        ]);

        $response = $this->postJson('/api/sales', [
            'branch_id' => $branch->id,
            'cash_session_id' => $cashSession->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 20,
                    'tax_amount' => 0,
                    'subtotal' => 40,
                    'total' => 40,
                ],
            ],
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 1,
        ]);

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('cash_movements', 0);
    }

    public function test_inventory_movement_filters_support_source_and_reference_id(): void
    {
        $product = Product::factory()->create();

        $saleMovement = InventoryMovement::factory()->create([
            'product_id' => $product->id,
            'type' => 'out',
            'source' => 'sale',
            'reference_id' => 123,
        ]);

        InventoryMovement::factory()->create([
            'product_id' => $product->id,
            'type' => 'out',
            'source' => 'manual',
            'reference_id' => null,
        ]);

        $response = $this->getJson('/api/inventory/movements?source=sale&reference_id=123');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $saleMovement->id)
            ->assertJsonPath('data.data.0.source', 'sale')
            ->assertJsonPath('data.data.0.reference_id', 123);
    }

    public function test_sale_rejects_cash_session_from_a_different_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        $cashSession = CashSession::factory()->open()->create([
            'branch_id' => $branchA->id,
            'device_identifier' => 'POS-01',
        ]);

        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'price' => 20,
        ]);

        $response = $this->postJson('/api/sales', [
            'branch_id' => $branchB->id,
            'cash_session_id' => $cashSession->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 20,
                    'tax_amount' => 0,
                    'subtotal' => 20,
                    'total' => 20,
                ],
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('errors.branch_id.0', 'The selected cash session does not belong to the given branch.');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('cash_movements', 0);
    }
}
