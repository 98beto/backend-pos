<?php

namespace Tests;

use App\Models\BranchProduct;
use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Device;
use App\Models\Product;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsDevice(?Branch $branch = null, array $deviceAttributes = []): Device
    {
        $branch ??= Branch::factory()->create();

        $device = Device::factory()->create(array_merge([
            'branch_id' => $branch->id,
        ], $deviceAttributes));

        $this->actingAs($device, 'sanctum');

        return $device;
    }

    protected function createProductInBranch(Branch $branch, array $productAttributes = [], array $branchAttributes = []): Product
    {
        $product = Product::factory()->create($productAttributes);

        BranchProduct::factory()->create(array_merge([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'price' => 20,
            'stock_quantity' => 10,
            'min_stock' => 5,
            'is_available' => true,
        ], $branchAttributes));

        return $product;
    }

    protected function createOpenCashSession(Device $device, array $attributes = []): CashSession
    {
        return CashSession::factory()->open()->create(array_merge([
            'branch_id' => $device->branch_id,
            'device_id' => $device->id,
        ], $attributes));
    }
}
