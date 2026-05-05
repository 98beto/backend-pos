<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashSessionBranchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_cash_sessions_by_branch_id(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        $sessionA = CashSession::factory()->open()->create([
            'branch_id' => $branchA->id,
            'device_identifier' => 'POS-01',
        ]);

        CashSession::factory()->open()->create([
            'branch_id' => $branchB->id,
            'device_identifier' => 'POS-02',
        ]);

        $response = $this->getJson("/api/cash-sessions?branch_id={$branchA->id}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $sessionA->id)
            ->assertJsonPath('data.data.0.branch_id', $branchA->id);
    }
}
