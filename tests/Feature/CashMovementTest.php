<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_a_manual_cash_movement_for_an_open_session(): void
    {
        $branch = Branch::factory()->create();

        $session = CashSession::factory()->open()->create([
            'branch_id' => $branch->id,
            'device_identifier' => 'POS-01',
        ]);

        $response = $this->postJson("/api/cash-sessions/{$session->id}/movements", [
            'type' => 'out',
            'category' => 'withdrawal',
            'amount' => 150,
            'notes' => 'Retiro de caja',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.cash_session_id', $session->id)
            ->assertJsonPath('data.branch_id', $branch->id)
            ->assertJsonPath('data.branch.id', $branch->id)
            ->assertJsonPath('data.branch.name', $branch->name)
            ->assertJsonPath('data.category', 'withdrawal');
    }

    public function test_it_rejects_manual_sale_category_movements(): void
    {
        $branch = Branch::factory()->create();

        $session = CashSession::factory()->open()->create([
            'branch_id' => $branch->id,
            'device_identifier' => 'POS-01',
        ]);

        $response = $this->postJson("/api/cash-sessions/{$session->id}/movements", [
            'type' => 'in',
            'category' => 'sale',
            'amount' => 50,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('errors.category.0', 'The sale category is reserved for automatic sale movements.');
    }

    public function test_it_rejects_movements_for_closed_sessions(): void
    {
        $branch = Branch::factory()->create();

        $session = CashSession::factory()->create([
            'branch_id' => $branch->id,
            'device_identifier' => 'POS-01',
            'status' => 'closed',
        ]);

        $response = $this->postJson("/api/cash-sessions/{$session->id}/movements", [
            'type' => 'out',
            'category' => 'withdrawal',
            'amount' => 50,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot record movements on a closed cash session.');
    }

    public function test_it_requires_notes_for_adjustment_movements(): void
    {
        $branch = Branch::factory()->create();

        $session = CashSession::factory()->open()->create([
            'branch_id' => $branch->id,
            'device_identifier' => 'POS-01',
        ]);

        $response = $this->postJson("/api/cash-sessions/{$session->id}/movements", [
            'type' => 'in',
            'category' => 'adjustment',
            'amount' => 25,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('errors.notes.0', 'Notes are required when recording an adjustment.');
    }
}
