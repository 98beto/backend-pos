<?php

namespace App\Actions\CashSessions;

use App\Models\CashSession;
use App\Support\CashSessionRules;

class OpenCashSession
{
    public function handle(array $validated): CashSession
    {
        CashSessionRules::ensureNoOpenSessionForBranchDevice(
            (int) $validated['branch_id'],
            $validated['device_identifier'],
        );

        return CashSession::create([
            'branch_id' => $validated['branch_id'],
            'device_identifier' => $validated['device_identifier'],
            'opening_balance' => $validated['opening_balance'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }
}
