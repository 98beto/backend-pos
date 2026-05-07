<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardBranchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_authenticated_device_branch_for_metrics(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $deviceA = $this->actingAsDevice($branchA, ['identifier' => 'POS-01']);
        $sessionA = $this->createOpenCashSession($deviceA, [
            'opening_balance' => 500,
        ]);

        $productA = $this->createProductInBranch($branchA, ['sku' => 'DASH-001'], [
            'stock_quantity' => 2,
            'min_stock' => 5,
        ]);

        $deviceB = $this->actingAsDevice($branchB, ['identifier' => 'POS-02']);
        $sessionB = $this->createOpenCashSession($deviceB, [
            'opening_balance' => 300,
        ]);

        $productB = $this->createProductInBranch($branchB, ['sku' => 'DASH-002'], [
            'stock_quantity' => 10,
            'min_stock' => 5,
            'is_available' => false,
        ]);

        $saleA = Sale::create([
            'customer_id' => null,
            'cash_session_id' => $sessionA->id,
            'branch_id' => $branchA->id,
            'payment_method' => 'cash',
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'status' => 'completed',
            'sale_date' => now(),
        ]);

        SaleDetail::create([
            'sale_id' => $saleA->id,
            'product_id' => $productA->id,
            'quantity' => 2,
            'unit_price' => 50,
            'tax_amount' => 0,
            'subtotal' => 100,
            'total' => 100,
        ]);

        $saleB = Sale::create([
            'customer_id' => null,
            'cash_session_id' => $sessionB->id,
            'branch_id' => $branchB->id,
            'payment_method' => 'card',
            'subtotal' => 200,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 200,
            'status' => 'completed',
            'sale_date' => now(),
        ]);

        SaleDetail::create([
            'sale_id' => $saleB->id,
            'product_id' => $productB->id,
            'quantity' => 4,
            'unit_price' => 50,
            'tax_amount' => 0,
            'subtotal' => 200,
            'total' => 200,
        ]);

        $this->actingAs($deviceA, 'sanctum');

        $response = $this->getJson('/api/dashboard');

        $response
            ->assertOk()
            ->assertJsonPath('data.today.sales_count', 1)
            ->assertJsonPath('data.today.revenue', 100)
            ->assertJsonPath('data.today.items_sold', 2)
            ->assertJsonPath('data.cash_session.id', $sessionA->id)
            ->assertJsonPath('data.cash_session.opening_balance', 500)
            ->assertJsonPath('data.inventory.total_products', 1)
            ->assertJsonPath('data.inventory.active_products', 1)
            ->assertJsonPath('data.inventory.low_stock_count', 1);
    }
}
