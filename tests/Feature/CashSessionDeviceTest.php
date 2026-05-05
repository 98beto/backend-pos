<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashSessionDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_allows_open_sessions_for_different_devices_in_the_same_branch(): void
    {
        $branch = Branch::factory()->create();

        CashSession::factory()->open()->create([
            'branch_id' => $branch->id,
            'device_identifier' => 'POS-01',
        ]);

        $response = $this->postJson('/api/cash-sessions/open', [
            'branch_id' => $branch->id,
            'device_identifier' => 'POS-02',
            'opening_balance' => 300,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.branch_id', $branch->id)
            ->assertJsonPath('data.branch.id', $branch->id)
            ->assertJsonPath('data.branch.name', $branch->name)
            ->assertJsonPath('data.device_identifier', 'POS-02');
    }

    public function test_it_blocks_a_second_open_session_for_the_same_branch_and_device(): void
    {
        $branch = Branch::factory()->create();

        CashSession::factory()->open()->create([
            'branch_id' => $branch->id,
            'device_identifier' => 'POS-01',
        ]);

        $response = $this->postJson('/api/cash-sessions/open', [
            'branch_id' => $branch->id,
            'device_identifier' => 'POS-01',
            'opening_balance' => 300,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'A cash session is already open for this branch and device.');
    }

    public function test_current_requires_branch_and_device_filters(): void
    {
        $response = $this->getJson('/api/cash-sessions/current');

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_current_returns_the_open_session_for_the_given_branch_and_device(): void
    {
        $branch = Branch::factory()->create();

        $session = CashSession::factory()->open()->create([
            'branch_id' => $branch->id,
            'device_identifier' => 'POS-01',
        ]);

        CashSession::factory()->open()->create([
            'branch_id' => $branch->id,
            'device_identifier' => 'POS-02',
        ]);

        $response = $this->getJson("/api/cash-sessions/current?branch_id={$branch->id}&device_identifier=POS-01");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $session->id)
            ->assertJsonPath('data.branch.id', $branch->id)
            ->assertJsonPath('data.device_identifier', 'POS-01');
    }

    public function test_close_uses_cash_movements_to_calculate_expected_balance(): void
    {
        $branch = Branch::factory()->create();

        $session = CashSession::factory()->open()->create([
            'branch_id' => $branch->id,
            'device_identifier' => 'POS-01',
            'opening_balance' => 500,
        ]);

        CashMovement::factory()->create([
            'cash_session_id' => $session->id,
            'branch_id' => $branch->id,
            'type' => 'in',
            'category' => 'sale',
            'amount' => 100,
            'source' => 'sale',
        ]);

        CashMovement::factory()->create([
            'cash_session_id' => $session->id,
            'branch_id' => $branch->id,
            'type' => 'out',
            'category' => 'withdrawal',
            'amount' => 40,
            'source' => 'manual',
        ]);

        $response = $this->postJson("/api/cash-sessions/{$session->id}/close", [
            'closing_balance' => 565,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.session.branch.id', $branch->id)
            ->assertJsonPath('data.expected_balance', 560)
            ->assertJsonPath('data.actual_balance', 565)
            ->assertJsonPath('data.difference', 5);
    }
}
