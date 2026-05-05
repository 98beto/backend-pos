<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryMovementRequest;
use App\Http\Resources\InventoryMovementResource;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryMovementController extends Controller
{
    /**
     * Display a listing of inventory movements.
     *
     * Supported query parameters:
     *   ?product_id=  Filter by product
     *   ?branch_id=   Filter by branch
     *   ?type=        Filter by movement type (in, out, adjustment)
     */
    public function index(Request $request)
    {
        $movements = InventoryMovement::with('product', 'branch')
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->branch_id, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($request->type,       fn ($q, $type) => $q->where('type', $type))
            ->when($request->source,     fn ($q, $source) => $q->where('source', $source))
            ->when($request->reference_id, fn ($q, $referenceId) => $q->where('reference_id', $referenceId))
            ->latest()
            ->paginate(20);

        $resource = InventoryMovementResource::collection($movements)
            ->response()
            ->getData(true);

        return response()->json([
            'success' => true,
            'data' => $resource,
        ]);
    }

    /**
     * Record a new manual inventory movement (in, out, or adjustment).
     * Also updates the product's stock_quantity accordingly.
     */
    public function store(StoreInventoryMovementRequest $request)
    {
        try {
            $movement = DB::transaction(function () use ($request) {
                $product = Product::lockForUpdate()->findOrFail($request->product_id);

                // For 'out' movements, check there is enough stock
                if ($request->type === 'out' && $product->stock_quantity < $request->quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => "Insufficient stock for \"{$product->name}\". "
                            ."Available: {$product->stock_quantity}, Requested: {$request->quantity}.",
                    ]);
                }

                // Update stock based on movement type
                match ($request->type) {
                    'in' => $product->increment('stock_quantity', $request->quantity),
                    'out' => $product->decrement('stock_quantity', $request->quantity),
                    'adjustment' => $product->update(['stock_quantity' => $request->quantity]),
                };

                return InventoryMovement::create([
                    'product_id' => $request->product_id,
                    'branch_id' => $request->branch_id,
                    'type' => $request->type,
                    'quantity' => $request->quantity,
                    'source' => 'manual',
                    'reference_id' => null,
                    'notes' => $request->notes,
                ]);
            });

            $movement->load('product', 'branch');

            return response()->json([
                'success' => true,
                'message' => 'Inventory movement recorded successfully.',
                'data' => new InventoryMovementResource($movement),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while recording the movement.',
            ], 500);
        }
    }

    /**
     * Display the specified inventory movement.
     */
    public function show(InventoryMovement $movement)
    {
        $movement->load('product', 'branch');

        return response()->json([
            'success' => true,
            'data' => new InventoryMovementResource($movement),
        ]);
    }
}
