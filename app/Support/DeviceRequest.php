<?php

namespace App\Support;

use App\Models\BranchProduct;
use App\Models\Device;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeviceRequest
{
    public static function device(Request $request): Device
    {
        /** @var Device $device */
        $device = $request->user();

        return $device;
    }

    public static function branchId(Request $request): int
    {
        return (int) self::device($request)->branch_id;
    }

    public static function branchProduct(Request $request, Product $product): BranchProduct
    {
        $branchProduct = $product->branchProducts()
            ->where('branch_id', self::branchId($request))
            ->first();

        if (! $branchProduct) {
            throw ValidationException::withMessages([
                'product_id' => 'The selected product is not available in this branch.',
            ]);
        }

        return $branchProduct;
    }
}
