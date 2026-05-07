<?php

namespace App\Http\Controllers\Api;

use App\Actions\CashSessions\CloseCashSession;
use App\Actions\CashSessions\OpenCashSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\CloseCashSessionRequest;
use App\Http\Requests\OpenCashSessionRequest;
use App\Http\Resources\CashSessionResource;
use App\Models\CashSession;
use App\Support\CashSessionRules;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CashSessionController extends Controller
{
    /**
     * Display a listing of all cash sessions.
     */
    public function index(Request $request)
    {
        $device = $this->currentDevice();

        $sessions = CashSession::where('branch_id', $device->branch_id)
            ->with('branch', 'device')
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
        CashSessionRules::ensureCashSessionBelongsToDevice($cashSession, $this->currentDevice());

        $cashSession->load('sales', 'branch', 'device');

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
        $device = $this->currentDevice();

        $session = CashSession::open()
            ->where('device_id', $device->id)
            ->with('branch', 'device')
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
    public function open(OpenCashSessionRequest $request, OpenCashSession $openCashSession)
    {
        try {
            $session = $openCashSession->handle($this->currentDevice(), $request->validated());
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        $session->load('branch', 'device');

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
    public function close(CloseCashSessionRequest $request, CashSession $cashSession, CloseCashSession $closeCashSession)
    {
        CashSessionRules::ensureCashSessionBelongsToDevice($cashSession, $this->currentDevice());

        if ($cashSession->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'This cash session is already closed.',
            ], 422);
        }

        $cashSession->load('branch', 'device');
        $result = $closeCashSession->handle($cashSession, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cash session closed successfully.',
            'data' => [
                'session' => new CashSessionResource($result['session']),
                'expected_balance' => $result['expected_balance'],
                'actual_balance' => $result['actual_balance'],
                'difference' => $result['difference'],
            ],
        ]);
    }
}
