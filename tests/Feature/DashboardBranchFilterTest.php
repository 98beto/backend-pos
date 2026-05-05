<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardBranchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_filters_today_metrics_and_open_session_by_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 20,
            'is_active' => true,
        ]);

        $sessionA = CashSession::factory()->open()->create([
            'branch_id' => $branchA->id,
            'device_identifier' => 'POS-01',
            'opening_balance' => 500,
        ]);

        CashSession::factory()->open()->create([
            'branch_id' => $branchB->id,
            'device_identifier' => 'POS-02',
            'opening_balance' => 300,
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
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50,
            'tax_amount' => 0,
            'subtotal' => 100,
            'total' => 100,
        ]);

        $saleB = Sale::create([
            'customer_id' => null,
            'cash_session_id' => CashSession::factory()->open()->create([
                'branch_id' => $branchB->id,
                'device_identifier' => 'POS-03',
            ])->id,
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
            'product_id' => $product->id,
            'quantity' => 4,
            'unit_price' => 50,
            'tax_amount' => 0,
            'subtotal' => 200,
            'total' => 200,
        ]);

        $response = $this->getJson("/api/dashboard?branch_id={$branchA->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.today.sales_count', 1)
            ->assertJsonPath('data.today.revenue', 100)
            ->assertJsonPath('data.today.items_sold', 2)
            ->assertJsonPath('data.cash_session.id', $sessionA->id)
            ->assertJsonPath('data.cash_session.opening_balance', 500);
    }

    public function test_dashboard_inventory_snapshot_remains_global_when_branch_filter_is_present(): void
    {
        $branch = Branch::factory()->create();

        Product::factory()->create([
            'is_active' => true,
            'stock_quantity' => 2,
            'min_stock' => 5,
        ]);

        Product::factory()->create([
            'is_active' => false,
            'stock_quantity' => 10,
            'min_stock' => 5,
        ]);

        $response = $this->getJson("/api/dashboard?branch_id={$branch->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.inventory.total_products', 2)
            ->assertJsonPath('data.inventory.active_products', 1)
            ->assertJsonPath('data.inventory.low_stock_count', 1);
    }
}
