<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashSessionDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_opens_a_cash_session_for_the_authenticated_device(): void
    {
        $branch = Branch::factory()->create();
        $device = $this->actingAsDevice($branch, ['identifier' => 'POS-01']);

        $response = $this->postJson('/api/cash-sessions/open', [
            'opening_balance' => 300,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.branch_id', $branch->id)
            ->assertJsonPath('data.device_id', $device->id)
            ->assertJsonPath('data.device.identifier', 'POS-01');
    }

    public function test_it_blocks_a_second_open_session_for_the_same_device(): void
    {
        $branch = Branch::factory()->create();
        $device = $this->actingAsDevice($branch, ['identifier' => 'POS-01']);

        $this->createOpenCashSession($device);

        $response = $this->postJson('/api/cash-sessions/open', [
            'opening_balance' => 300,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('errors.device_id.0', 'A cash session is already open for this device.');
    }

    public function test_current_requires_authentication(): void
    {
        $this->getJson('/api/cash-sessions/current')->assertStatus(401);
    }

    public function test_current_returns_the_open_session_for_the_authenticated_device(): void
    {
        $branch = Branch::factory()->create();
        $device = $this->actingAsDevice($branch, ['identifier' => 'POS-01']);

        $session = $this->createOpenCashSession($device);

        $otherDevice = Device::factory()->create([
            'branch_id' => $branch->id,
            'identifier' => 'POS-02',
        ]);

        $this->createOpenCashSession($otherDevice);

        $response = $this->getJson('/api/cash-sessions/current');

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $session->id)
            ->assertJsonPath('data.branch.id', $branch->id)
            ->assertJsonPath('data.device.identifier', 'POS-01');
    }

    public function test_close_uses_cash_movements_to_calculate_expected_balance(): void
    {
        $branch = Branch::factory()->create();
        $device = $this->actingAsDevice($branch, ['identifier' => 'POS-01']);

        $session = $this->createOpenCashSession($device, [
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
            ->assertJsonPath('data.session.device.id', $device->id)
            ->assertJsonPath('data.expected_balance', 560)
            ->assertJsonPath('data.actual_balance', 565)
            ->assertJsonPath('data.difference', 5);
    }
}
