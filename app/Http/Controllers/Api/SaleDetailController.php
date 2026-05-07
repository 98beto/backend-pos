<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SaleDetailResource;
use App\Models\SaleDetail;
use Illuminate\Http\Request;

class SaleDetailController extends Controller
{
    /**
     * Display a listing of sale details.
     * Optionally filter by sale_id and branch_id.
     */
    public function index(Request $request)
    {
        $branchId = $this->currentDevice()->branch_id;

        $details = SaleDetail::with('product', 'sale.branch')
            ->when($request->sale_id, fn ($q) => $q->where('sale_id', $request->sale_id))
            ->whereHas('sale', fn ($saleQuery) => $saleQuery->where('branch_id', $branchId))
            ->latest()
            ->paginate(20);

        $resource = SaleDetailResource::collection($details)
            ->response()
            ->getData(true);

        return response()->json([
            'success' => true,
            'data' => $resource,
        ]);
    }

    /**
     * Display the specified sale detail.
     */
    public function show(SaleDetail $saleDetail)
    {
        if ((int) $saleDetail->sale?->branch_id !== (int) $this->currentDevice()->branch_id) {
            abort(404);
        }

        $saleDetail->load('product', 'sale.branch');

        return response()->json([
            'success' => true,
            'data' => new SaleDetailResource($saleDetail),
        ]);
    }
}
