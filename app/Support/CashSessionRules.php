<?php

namespace App\Support;

use App\Models\CashSession;
use App\Models\Device;
use Illuminate\Validation\ValidationException;

class CashSessionRules
{
    public static function ensureCashSessionIsOpen(CashSession $cashSession): void
    {
        if ($cashSession->status !== 'open') {
            throw ValidationException::withMessages([
                'cash_session_id' => 'The selected cash session must be open.',
            ]);
        }
    }

    public static function ensureCashSessionBelongsToBranch(CashSession $cashSession, int $branchId): void
    {
        if ((int) $cashSession->branch_id !== $branchId) {
            throw ValidationException::withMessages([
                'branch_id' => 'The selected cash session does not belong to the given branch.',
            ]);
        }
    }

    public static function ensureCashSessionBelongsToDevice(CashSession $cashSession, Device $device): void
    {
        if ((int) $cashSession->device_id !== (int) $device->id) {
            throw ValidationException::withMessages([
                'cash_session_id' => 'The selected cash session does not belong to the authenticated device.',
            ]);
        }
    }

    public static function ensureNoOpenSessionForDevice(int $deviceId): void
    {
        if (CashSession::where('status', 'open')
            ->where('device_id', $deviceId)
            ->exists()) {
            throw ValidationException::withMessages([
                'device_id' => 'A cash session is already open for this device.',
            ]);
        }
    }

    public static function expectedBalance(CashSession $cashSession): float
    {
        $cashIn = (float) $cashSession->cashMovements()->where('type', 'in')->sum('amount');
        $cashOut = (float) $cashSession->cashMovements()->where('type', 'out')->sum('amount');

        return (float) $cashSession->opening_balance + $cashIn - $cashOut;
    }
}
