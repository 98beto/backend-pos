<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $brands = Brand::orderBy("name", "asc")->paginate(20);

        $resource = BrandResource::collection($brands)
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
    public function store(StoreBrandRequest $request)
    {
        $brand = Brand::create($request->validated());

        return response()->json(
            [
                "success" => true,
                "message" => "Brand created successfully.",
                "data" => new BrandResource($brand),
            ],
            201,
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
        return response()->json([
            "success" => true,
            "data" => new BrandResource($brand),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        $brand->update($request->validated());

        return response()->json([
            "success" => true,
            "message" => "Brand updated successfully.",
            "data" => new BrandResource($brand),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        if ($brand->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete brand with associated products.',
            ], 422);
        }

        $brand->delete();

        return response()->json([
            "success" => true,
            "message" => "Brand deleted successfully.",
        ]);
    }
}
