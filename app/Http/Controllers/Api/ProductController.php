<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     *
     * Supported query parameters:
     *   ?search=      Search by name, SKU, or barcode (ILIKE)
     *   ?category_id= Filter by category
     *   ?brand_id=    Filter by brand
     *   ?is_active=   Filter by active status (1 or 0)
     *   ?low_stock=1  Only products where stock_quantity <= min_stock
     */
    public function index(Request $request)
    {
        $products = Product::with("category", "brand")
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where("name", "ILIKE", "%{$search}%")
                        ->orWhere("sku", "ILIKE", "%{$search}%")
                        ->orWhere("barcode", "ILIKE", "%{$search}%");
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
                $request->filled("is_active"),
                fn($q) => $q->where(
                    "is_active",
                    filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN),
                ),
            )
            ->when(
                $request->low_stock,
                fn($q) => $q->whereColumn("stock_quantity", "<=", "min_stock"),
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
        $product = Product::create(
            array_merge($request->validated(), [
                "min_stock" => $request->min_stock ?? 5,
            ]),
        );

        $product->load("category", "brand");

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
        $product->load("category", "brand");

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
        $product->update(
            array_merge($request->validated(), [
                "min_stock" => $request->min_stock ?? $product->min_stock,
            ]),
        );

        $product->load("category", "brand");

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
        $product->delete();

        return response()->json([
            "success" => true,
            "message" => "Product deleted successfully.",
        ]);
    }

    /**
     * Return all products that are at or below their minimum stock level.
     */
    public function lowStock()
    {
        $products = Product::with("category", "brand")
            ->lowStock()
            ->orderBy("stock_quantity", "asc")
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
