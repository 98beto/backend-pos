<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Device;
use App\Models\SavedCart;
use App\Models\SavedCartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedCartBranchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_saved_carts_for_the_authenticated_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $this->actingAsDevice($branchA);

        $expected = SavedCart::factory()->create([
            'branch_id' => $branchA->id,
        ]);

        SavedCart::factory()->create([
            'branch_id' => $branchB->id,
        ]);

        $response = $this->getJson('/api/saved-carts');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $expected->id)
            ->assertJsonPath('data.data.0.branch_id', $branchA->id);
    }

    public function test_it_stores_branch_id_on_saved_carts_from_device_context(): void
    {
        $branch = Branch::factory()->create();
        $device = $this->actingAsDevice($branch);
        $product = $this->createProductInBranch($branch, ['sku' => 'CART-001']);

        $response = $this->postJson('/api/saved-carts', [
            'name' => 'Mostrador 1',
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
            ->assertJsonPath('data.branch.id', $branch->id);

        $this->assertDatabaseHas('saved_carts', [
            'name' => 'Mostrador 1',
            'branch_id' => $branch->id,
        ]);
    }

    public function test_it_rejects_saving_a_cart_with_a_cash_session_from_another_device(): void
    {
        $branch = Branch::factory()->create();
        $deviceA = $this->actingAsDevice($branch, ['identifier' => 'POS-01']);
        $deviceB = Device::factory()->create([
            'branch_id' => $branch->id,
            'identifier' => 'POS-02',
        ]);
        $product = $this->createProductInBranch($branch, ['sku' => 'CART-002']);
        $cashSession = $this->createOpenCashSession($deviceA);

        $this->actingAs($deviceB, 'sanctum');

        $response = $this->postJson('/api/saved-carts', [
            'name' => 'Mostrador 1',
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
            ->assertJsonPath('errors.cash_session_id.0', 'The selected cash session does not belong to the authenticated device.');
    }

    public function test_it_updates_saved_cart_with_matching_cash_session(): void
    {
        $branch = Branch::factory()->create();
        $device = $this->actingAsDevice($branch, ['identifier' => 'POS-01']);
        $product = $this->createProductInBranch($branch, ['sku' => 'CART-003']);
        $cashSession = $this->createOpenCashSession($device);

        $savedCart = SavedCart::factory()->create([
            'branch_id' => $branch->id,
        ]);

        SavedCartItem::factory()->create([
            'saved_cart_id' => $savedCart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->putJson("/api/saved-carts/{$savedCart->id}", [
            'name' => 'Actualizado',
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
            ->assertJsonPath('data.cash_session_id', $cashSession->id)
            ->assertJsonPath('data.name', 'Actualizado');
    }
}
