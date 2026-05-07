<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleBranchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_sales_from_the_authenticated_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        $deviceA = $this->actingAsDevice($branchA, ['identifier' => 'POS-01']);
        $sessionA = $this->createOpenCashSession($deviceA);

        $saleA = Sale::factory()->create([
            'branch_id' => $branchA->id,
            'cash_session_id' => $sessionA->id,
        ]);

        $deviceB = $this->actingAsDevice($branchB, ['identifier' => 'POS-02']);
        $sessionB = $this->createOpenCashSession($deviceB);

        Sale::factory()->create([
            'branch_id' => $branchB->id,
            'cash_session_id' => $sessionB->id,
        ]);

        $this->actingAs($deviceA, 'sanctum');

        $response = $this->getJson('/api/sales');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $saleA->id)
            ->assertJsonPath('data.data.0.branch_id', $branchA->id);
    }
}
