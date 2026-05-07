<?php

namespace App\Actions\CashSessions;

use App\Models\CashSession;
use App\Models\Device;
use App\Support\CashSessionRules;

class OpenCashSession
{
    public function handle(Device $device, array $validated): CashSession
    {
        CashSessionRules::ensureNoOpenSessionForDevice($device->id);

        return CashSession::create([
            'branch_id' => $device->branch_id,
            'device_id' => $device->id,
            'opening_balance' => $validated['opening_balance'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }
}
