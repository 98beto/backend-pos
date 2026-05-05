<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleDetailBranchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_sale_details_by_branch_id(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $product = Product::factory()->create();

        $saleA = Sale::create([
            'customer_id' => null,
            'cash_session_id' => CashSession::factory()->open()->create([
                'branch_id' => $branchA->id,
                'device_identifier' => 'POS-01',
            ])->id,
            'branch_id' => $branchA->id,
            'payment_method' => 'cash',
            'subtotal' => 20,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 20,
            'status' => 'completed',
            'sale_date' => now(),
        ]);

        $detailA = SaleDetail::create([
            'sale_id' => $saleA->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 20,
            'tax_amount' => 0,
            'subtotal' => 20,
            'total' => 20,
        ]);

        $saleB = Sale::create([
            'customer_id' => null,
            'cash_session_id' => CashSession::factory()->open()->create([
                'branch_id' => $branchB->id,
                'device_identifier' => 'POS-02',
            ])->id,
            'branch_id' => $branchB->id,
            'payment_method' => 'cash',
            'subtotal' => 20,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 20,
            'status' => 'completed',
            'sale_date' => now(),
        ]);

        SaleDetail::create([
            'sale_id' => $saleB->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 20,
            'tax_amount' => 0,
            'subtotal' => 20,
            'total' => 20,
        ]);

        $response = $this->getJson("/api/sale-details?branch_id={$branchA->id}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $detailA->id)
            ->assertJsonPath('data.data.0.sale_id', $saleA->id)
            ->assertJsonPath('data.data.0.branch_id', $branchA->id)
            ->assertJsonPath('data.data.0.branch.id', $branchA->id)
            ->assertJsonPath('data.data.0.branch.name', $branchA->name);
    }

    public function test_it_can_combine_sale_id_and_branch_id_filters(): void
    {
        $branch = Branch::factory()->create();
        $product = Product::factory()->create();

        $sale = Sale::create([
            'customer_id' => null,
            'cash_session_id' => CashSession::factory()->open()->create([
                'branch_id' => $branch->id,
                'device_identifier' => 'POS-01',
            ])->id,
            'branch_id' => $branch->id,
            'payment_method' => 'cash',
            'subtotal' => 40,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 40,
            'status' => 'completed',
            'sale_date' => now(),
        ]);

        $detail = SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 20,
            'tax_amount' => 0,
            'subtotal' => 40,
            'total' => 40,
        ]);

        $response = $this->getJson("/api/sale-details?sale_id={$sale->id}&branch_id={$branch->id}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $detail->id)
            ->assertJsonPath('data.data.0.branch.id', $branch->id);
    }
}
