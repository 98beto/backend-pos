<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSavedCartRequest;
use App\Http\Requests\UpdateSavedCartRequest;
use App\Http\Resources\SavedCartResource;
use App\Models\CashSession;
use App\Models\SavedCart;
use App\Support\CashSessionRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavedCartController extends Controller
{
    /**
     * Display a listing of saved carts.
     */
    public function index(Request $request)
    {
        $savedCarts = SavedCart::with(['customer', 'cashSession', 'items.product'])
            ->with('branch')
            ->when($request->branch_id, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when(
                $request->status,
                fn ($q, $status) => $q->where('status', $status),
                fn ($q) => $q->where('status', 'saved'),
            )
            ->latest()
            ->paginate(20);

        $resource = SavedCartResource::collection($savedCarts)
            ->response()
            ->getData(true);

        return response()->json([
            'success' => true,
            'data' => $resource,
        ]);
    }

    /**
     * Store a newly created saved cart in storage.
     */
    public function store(StoreSavedCartRequest $request)
    {
        $validated = $request->validated();

        try {
            if (! empty($validated['cash_session_id'])) {
                $cashSession = CashSession::findOrFail($validated['cash_session_id']);

                CashSessionRules::ensureCashSessionBelongsToBranch($cashSession, (int) $validated['branch_id']);
            }
        } catch (ValidationException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
        }

        $savedCart = DB::transaction(function () use ($validated) {
            $savedCart = SavedCart::create([
                'name' => $validated['name'],
                'customer_id' => $validated['customer_id'] ?? null,
                'cash_session_id' => $validated['cash_session_id'] ?? null,
                'branch_id' => $validated['branch_id'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'status' => $validated['status'] ?? 'saved',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $savedCart->items()->create($item);
            }

            return $savedCart;
        });

        $savedCart->load(['customer', 'cashSession', 'items.product', 'branch']);

        return response()->json([
            'success' => true,
            'message' => 'Saved cart created successfully.',
            'data' => new SavedCartResource($savedCart),
        ], 201);
    }

    /**
     * Display the specified saved cart.
     */
    public function show(SavedCart $savedCart)
    {
        $savedCart->load(['customer', 'cashSession', 'items.product', 'branch']);

        return response()->json([
            'success' => true,
            'data' => new SavedCartResource($savedCart),
        ]);
    }

    /**
     * Update the specified saved cart in storage.
     */
    public function update(UpdateSavedCartRequest $request, SavedCart $savedCart)
    {
        $validated = $request->validated();

        try {
            if (! empty($validated['cash_session_id'])) {
                $cashSession = CashSession::findOrFail($validated['cash_session_id']);

                CashSessionRules::ensureCashSessionBelongsToBranch($cashSession, (int) $validated['branch_id']);
            }
        } catch (ValidationException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
        }

        DB::transaction(function () use ($validated, $savedCart) {
            $savedCart->update([
                'name' => $validated['name'],
                'customer_id' => $validated['customer_id'] ?? null,
                'cash_session_id' => $validated['cash_session_id'] ?? null,
                'branch_id' => $validated['branch_id'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'status' => $validated['status'] ?? 'saved',
                'notes' => $validated['notes'] ?? null,
            ]);

            $savedCart->items()->delete();

            foreach ($validated['items'] as $item) {
                $savedCart->items()->create($item);
            }
        });

        $savedCart->load(['customer', 'cashSession', 'items.product', 'branch']);

        return response()->json([
            'success' => true,
            'message' => 'Saved cart updated successfully.',
            'data' => new SavedCartResource($savedCart),
        ]);
    }

    /**
     * Remove the specified saved cart from storage.
     */
    public function destroy(SavedCart $savedCart)
    {
        $savedCart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Saved cart deleted successfully.',
        ]);
    }

    /**
     * Mark the specified saved cart as in progress.
     */
    public function recover(SavedCart $savedCart)
    {
        $savedCart->update([
            'status' => 'in_progress',
        ]);

        $savedCart->load(['customer', 'cashSession', 'items.product', 'branch']);

        return response()->json([
            'success' => true,
            'message' => 'Saved cart recovered successfully.',
            'data' => new SavedCartResource($savedCart),
        ]);
    }
}
