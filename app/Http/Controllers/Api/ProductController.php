<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UpdateProductBranchRequest;
use App\Http\Resources\ProductResource;
use App\Models\BranchProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     *
     * Supported query parameters:
     *   ?search=      Search by name or SKU (ILIKE)
     *   ?category_id= Filter by category
     *   ?brand_id=    Filter by brand
     *   ?low_stock=1  Only products where stock_quantity <= min_stock
     */
    public function index(Request $request)
    {
        $branchId = $this->currentDevice()->branch_id;

        $products = Product::with("category", "brand")
            ->whereHas('branchProducts', fn ($q) => $q->where('branch_id', $branchId))
            ->with(['currentBranchProduct' => fn ($q) => $q->where('branch_id', $branchId)->with('branch')])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where("name", "ILIKE", "%{$search}%")
                        ->orWhere("sku", "ILIKE", "%{$search}%");
                });
            })
            ->when(
                $request->category_id,
                fn($q, $id) => $q->where("category_id", $id),
            )
            ->when(
                $request->brand_id,
                fn($q, $id) => $q->where("brand_id", $id),
            )
            ->when(
                $request->low_stock,
                fn($q) => $q->whereHas('branchProducts', fn ($branchQuery) => $branchQuery
                    ->where('branch_id', $branchId)
                    ->whereColumn('stock_quantity', '<=', 'min_stock')),
            )
            ->orderBy("name", "asc")
            ->paginate(50);

        $resource = ProductResource::collection($products)
            ->response()
            ->getData(true);

        return response()->json([
            "success" => true,
            "data" => $resource,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $device = $this->currentDevice();
        $validated = $request->validated();

        $product = Product::firstOrCreate(
            ['sku' => $validated['sku']],
            [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'cost_price' => $validated['cost_price'] ?? null,
                'unit_measure' => $validated['unit_measure'] ?? 'PZA',
                'category_id' => $validated['category_id'] ?? null,
                'brand_id' => $validated['brand_id'] ?? null,
            ],
        );

        $branchProduct = BranchProduct::where('branch_id', $device->branch_id)
            ->where('product_id', $product->id)
            ->first();

        if ($branchProduct) {
            throw ValidationException::withMessages([
                'sku' => 'This product is already registered in the current branch.',
            ]);
        }

        $product->branchProducts()->create([
            'branch_id' => $device->branch_id,
            'price' => $validated['price'],
            'stock_quantity' => $validated['stock_quantity'],
            'min_stock' => $validated['min_stock'] ?? 5,
            'is_available' => $validated['is_available'] ?? true,
        ]);

        $product->load("category", "brand", 'currentBranchProduct.branch');

        return response()->json(
            [
                "success" => true,
                "message" => "Product created successfully.",
                "data" => new ProductResource($product),
            ],
            201,
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $branchId = $this->currentDevice()->branch_id;

        if (! $product->branchProducts()->where('branch_id', $branchId)->exists()) {
            abort(404);
        }

        $product->load(
            'category',
            'brand',
            ['currentBranchProduct' => fn ($q) => $q->where('branch_id', $branchId)->with('branch')],
        );

        return response()->json([
            "success" => true,
            "data" => new ProductResource($product),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $branchId = $this->currentDevice()->branch_id;

        $product->update($request->validated());

        $product->load(
            'category',
            'brand',
            ['currentBranchProduct' => fn ($q) => $q->where('branch_id', $branchId)->with('branch')],
        );

        return response()->json([
            "success" => true,
            "message" => "Product updated successfully.",
            "data" => new ProductResource($product),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $branchId = $this->currentDevice()->branch_id;

        $product->branchProducts()->where('branch_id', $branchId)->delete();

        if (! $product->branchProducts()->exists()) {
            $product->delete();
        }

        return response()->json([
            "success" => true,
            "message" => "Product deleted successfully.",
        ]);
    }

    public function updateBranch(UpdateProductBranchRequest $request, Product $product)
    {
        $branchId = $this->currentDevice()->branch_id;

        $branchProduct = $product->branchProducts()
            ->where('branch_id', $branchId)
            ->firstOrFail();

        $branchProduct->update([
            'price' => $request->validated('price'),
            'stock_quantity' => $request->validated('stock_quantity'),
            'min_stock' => $request->validated('min_stock', $branchProduct->min_stock),
            'is_available' => $request->validated('is_available', $branchProduct->is_available),
        ]);

        $product->load(
            'category',
            'brand',
            ['currentBranchProduct' => fn ($q) => $q->where('branch_id', $branchId)->with('branch')],
        );

        return response()->json([
            'success' => true,
            'message' => 'Product branch settings updated successfully.',
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Return all products that are at or below their minimum stock level.
     */
    public function lowStock()
    {
        $branchId = $this->currentDevice()->branch_id;

        $products = Product::with("category", "brand")
            ->whereHas('branchProducts', fn ($q) => $q
                ->where('branch_id', $branchId)
                ->whereColumn('stock_quantity', '<=', 'min_stock'))
            ->with(['currentBranchProduct' => fn ($q) => $q->where('branch_id', $branchId)->with('branch')])
            ->orderBy("name", "asc")
            ->paginate(20);

        $resource = ProductResource::collection($products)
            ->response()
            ->getData(true);

        return response()->json([
            "success" => true,
            "data" => $resource,
        ]);
    }
}
