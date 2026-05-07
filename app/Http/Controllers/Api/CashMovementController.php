<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashMovementRequest;
use App\Http\Resources\CashMovementResource;
use App\Models\CashSession;
use App\Support\CashMovementRules;
use App\Support\CashSessionRules;
use Illuminate\Validation\ValidationException;

class CashMovementController extends Controller
{
    /**
     * Display a listing of movements for the given cash session.
     */
    public function index(CashSession $cashSession)
    {
        CashSessionRules::ensureCashSessionBelongsToDevice($cashSession, $this->currentDevice());

        $movements = $cashSession->cashMovements()
            ->with('branch')
            ->latest()
            ->paginate(20);

        $resource = CashMovementResource::collection($movements)
            ->response()
            ->getData(true);

        return response()->json([
            'success' => true,
            'data' => $resource,
        ]);
    }

    /**
     * Store a manual cash movement for the given session.
     */
    public function store(StoreCashMovementRequest $request, CashSession $cashSession)
    {
        CashSessionRules::ensureCashSessionBelongsToDevice($cashSession, $this->currentDevice());

        try {
            CashSessionRules::ensureCashSessionIsOpen($cashSession);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot record movements on a closed cash session.',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $validated = $request->validated();

            CashMovementRules::ensureManualCategoryAllowed($validated['category']);
            CashMovementRules::ensureAdjustmentHasNotes($validated['category'], $validated['notes'] ?? null);

            $movement = $cashSession->cashMovements()->create([
                'branch_id' => $cashSession->branch_id,
                'type' => $validated['type'],
                'category' => $validated['category'],
                'amount' => $validated['amount'],
                'source' => 'manual',
                'reference_id' => null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $movement->load('branch');

            return response()->json([
                'success' => true,
                'message' => 'Cash movement recorded successfully.',
                'data' => new CashMovementResource($movement),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
