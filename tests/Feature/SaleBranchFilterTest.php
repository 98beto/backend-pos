<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleBranchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_sales_by_branch_id(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        $saleA = Sale::factory()->create([
            'branch_id' => $branchA->id,
            'cash_session_id' => CashSession::factory()->open()->create([
                'branch_id' => $branchA->id,
                'device_identifier' => 'POS-01',
            ])->id,
        ]);

        Sale::factory()->create([
            'branch_id' => $branchB->id,
            'cash_session_id' => CashSession::factory()->open()->create([
                'branch_id' => $branchB->id,
                'device_identifier' => 'POS-02',
            ])->id,
        ]);

        $response = $this->getJson("/api/sales?branch_id={$branchA->id}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $saleA->id)
            ->assertJsonPath('data.data.0.branch_id', $branchA->id)
            ->assertJsonPath('data.data.0.branch.id', $branchA->id)
            ->assertJsonPath('data.data.0.branch.name', $branchA->name);
    }
}
