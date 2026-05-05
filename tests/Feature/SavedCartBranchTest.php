<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\SavedCart;
use App\Models\SavedCartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedCartBranchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_saved_carts_by_branch_id(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        $expected = SavedCart::factory()->create([
            'branch_id' => $branchA->id,
        ]);

        SavedCart::factory()->create([
            'branch_id' => $branchB->id,
        ]);

        $response = $this->getJson("/api/saved-carts?branch_id={$branchA->id}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $expected->id)
            ->assertJsonPath('data.data.0.branch_id', $branchA->id)
            ->assertJsonPath('data.data.0.branch.id', $branchA->id)
            ->assertJsonPath('data.data.0.branch.name', $branchA->name);
    }

    public function test_it_stores_branch_id_on_saved_carts(): void
    {
        $branch = Branch::factory()->create();
        $product = Product::factory()->create();

        $response = $this->postJson('/api/saved-carts', [
            'name' => 'Mostrador 1',
            'branch_id' => $branch->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 20,
                    'tax_amount' => 0,
                    'subtotal' => 40,
                    'total' => 40,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.branch_id', $branch->id)
            ->assertJsonPath('data.branch.id', $branch->id)
            ->assertJsonPath('data.branch.name', $branch->name);

        $this->assertDatabaseHas('saved_carts', [
            'name' => 'Mostrador 1',
            'branch_id' => $branch->id,
        ]);
    }

    public function test_it_rejects_saving_a_cart_with_a_cash_session_from_another_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $product = Product::factory()->create();
        $cashSession = CashSession::factory()->open()->create([
            'branch_id' => $branchA->id,
            'device_identifier' => 'POS-01',
        ]);

        $response = $this->postJson('/api/saved-carts', [
            'name' => 'Mostrador 1',
            'branch_id' => $branchB->id,
            'cash_session_id' => $cashSession->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 20,
                    'tax_amount' => 0,
                    'subtotal' => 20,
                    'total' => 20,
                ],
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'The selected cash session does not belong to the given branch.');
    }

    public function test_it_updates_saved_cart_with_branch_and_matching_cash_session(): void
    {
        $branch = Branch::factory()->create();
        $product = Product::factory()->create();
        $cashSession = CashSession::factory()->open()->create([
            'branch_id' => $branch->id,
            'device_identifier' => 'POS-01',
        ]);

        $savedCart = SavedCart::factory()->create([
            'branch_id' => $branch->id,
        ]);

        SavedCartItem::factory()->create([
            'saved_cart_id' => $savedCart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->putJson("/api/saved-carts/{$savedCart->id}", [
            'name' => 'Actualizado',
            'branch_id' => $branch->id,
            'cash_session_id' => $cashSession->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price' => 20,
                    'tax_amount' => 0,
                    'subtotal' => 60,
                    'total' => 60,
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.branch_id', $branch->id)
            ->assertJsonPath('data.branch.id', $branch->id)
            ->assertJsonPath('data.cash_session_id', $cashSession->id)
            ->assertJsonPath('data.name', 'Actualizado');
    }

    public function test_it_rejects_updating_a_saved_cart_with_a_cash_session_from_another_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $product = Product::factory()->create();
        $cashSession = CashSession::factory()->open()->create([
            'branch_id' => $branchB->id,
            'device_identifier' => 'POS-02',
        ]);

        $savedCart = SavedCart::factory()->create([
            'branch_id' => $branchA->id,
        ]);

        $response = $this->putJson("/api/saved-carts/{$savedCart->id}", [
            'name' => 'Actualizado',
            'branch_id' => $branchA->id,
            'cash_session_id' => $cashSession->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 20,
                    'tax_amount' => 0,
                    'subtotal' => 20,
                    'total' => 20,
                ],
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'The selected cash session does not belong to the given branch.');
    }
}
