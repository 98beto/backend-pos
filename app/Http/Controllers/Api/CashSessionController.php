<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseCashSessionRequest;
use App\Http\Requests\OpenCashSessionRequest;
use App\Http\Resources\CashSessionResource;
use App\Models\CashSession;
use App\Support\CashSessionRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CashSessionController extends Controller
{
    /**
     * Display a listing of all cash sessions.
     */
    public function index(Request $request)
    {
        $sessions = CashSession::when(
                $request->branch_id,
                fn ($q, $branchId) => $q->where('branch_id', $branchId),
            )
            ->with('branch')
            ->latest()
            ->paginate(20);

        $resource = CashSessionResource::collection($sessions)
            ->response()
            ->getData(true);

        return response()->json([
            'success' => true,
            'data' => $resource,
        ]);
    }

    /**
     * Display the specified cash session with its sales summary.
     */
    public function show(CashSession $cashSession)
    {
        $cashSession->load('sales', 'branch');

        return response()->json([
            'success' => true,
            'data' => new CashSessionResource($cashSession),
        ]);
    }

    /**
     * Get the currently open cash session, if any.
     */
    public function current(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'device_identifier' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $session = CashSession::where('status', 'open')
            ->where('branch_id', $validated['branch_id'])
            ->where('device_identifier', $validated['device_identifier'])
            ->with('branch')
            ->latest('opened_at')
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'No open cash session found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CashSessionResource($session),
        ]);
    }

    /**
     * Open a new cash session.
     * Only one session can be open at a time.
     */
    public function open(OpenCashSessionRequest $request)
    {
        try {
            CashSessionRules::ensureNoOpenSessionForBranchDevice(
                (int) $request->branch_id,
                $request->device_identifier,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        $session = CashSession::create([
            'branch_id' => $request->branch_id,
            'device_identifier' => $request->device_identifier,
            'opening_balance' => $request->opening_balance,
            'notes' => $request->notes,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $session->load('branch');

        return response()->json([
            'success' => true,
            'message' => 'Cash session opened successfully.',
            'data' => new CashSessionResource($session),
        ], 201);
    }

    /**
     * Close an existing cash session.
     * Calculates the difference between expected and actual closing balance.
     */
    public function close(CloseCashSessionRequest $request, CashSession $cashSession)
    {
        if ($cashSession->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'This cash session is already closed.',
            ], 422);
        }

        $cashSession->update([
            'closing_balance' => $request->closing_balance,
            'closed_at' => now(),
            'status' => 'closed',
            'notes' => $request->notes ?? $cashSession->notes,
        ]);

        $cashSession->load('branch');
        $expectedBalance = CashSessionRules::expectedBalance($cashSession);

        $difference = $request->closing_balance - $expectedBalance;

        return response()->json([
            'success' => true,
            'message' => 'Cash session closed successfully.',
            'data' => [
                'session' => new CashSessionResource($cashSession),
                'expected_balance' => round($expectedBalance, 2),
                'actual_balance' => round((float) $request->closing_balance, 2),
                'difference' => round($difference, 2),
            ],
        ]);
    }
}
