<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Device;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleDetailBranchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_sale_details_for_the_authenticated_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        $deviceA = $this->actingAsDevice($branchA, ['identifier' => 'POS-01']);
        $sessionA = $this->createOpenCashSession($deviceA);
        $productA = $this->createProductInBranch($branchA, ['sku' => 'SD-001']);

        $saleA = Sale::create([
            'customer_id' => null,
            'cash_session_id' => $sessionA->id,
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
            'product_id' => $productA->id,
            'quantity' => 1,
            'unit_price' => 20,
            'tax_amount' => 0,
            'subtotal' => 20,
            'total' => 20,
        ]);

        $deviceB = Device::factory()->create([
            'branch_id' => $branchB->id,
            'identifier' => 'POS-02',
        ]);
        $sessionB = $this->createOpenCashSession($deviceB);
        $productB = $this->createProductInBranch($branchB, ['sku' => 'SD-002']);

        $saleB = Sale::create([
            'customer_id' => null,
            'cash_session_id' => $sessionB->id,
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
            'product_id' => $productB->id,
            'quantity' => 1,
            'unit_price' => 20,
            'tax_amount' => 0,
            'subtotal' => 20,
            'total' => 20,
        ]);

        $this->actingAs($deviceA, 'sanctum');

        $response = $this->getJson('/api/sale-details');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $detailA->id)
            ->assertJsonPath('data.data.0.sale_id', $saleA->id)
            ->assertJsonPath('data.data.0.branch_id', $branchA->id);
    }

    public function test_it_can_filter_by_sale_id_within_the_authenticated_branch(): void
    {
        $branch = Branch::factory()->create();
        $device = $this->actingAsDevice($branch, ['identifier' => 'POS-01']);
        $session = $this->createOpenCashSession($device);
        $product = $this->createProductInBranch($branch, ['sku' => 'SD-003']);

        $sale = Sale::create([
            'customer_id' => null,
            'cash_session_id' => $session->id,
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

        $response = $this->getJson("/api/sale-details?sale_id={$sale->id}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $detail->id)
            ->assertJsonPath('data.data.0.branch.id', $branch->id);
    }
}
