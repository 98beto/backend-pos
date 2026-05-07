<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashSessionBranchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_cash_sessions_for_the_authenticated_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        $deviceA = $this->actingAsDevice($branchA, ['identifier' => 'POS-01']);
        $sessionA = $this->createOpenCashSession($deviceA);

        $deviceB = Device::factory()->create([
            'branch_id' => $branchB->id,
            'identifier' => 'POS-02',
        ]);

        $this->createOpenCashSession($deviceB);

        $this->actingAs($deviceA, 'sanctum');

        $response = $this->getJson('/api/cash-sessions');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $sessionA->id)
            ->assertJsonPath('data.data.0.branch_id', $branchA->id);
    }
}
