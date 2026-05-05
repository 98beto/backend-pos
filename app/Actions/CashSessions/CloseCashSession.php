<?php

namespace App\Actions\CashSessions;

use App\Models\CashSession;
use App\Support\CashSessionRules;

class CloseCashSession
{
    public function handle(CashSession $cashSession, array $validated): array
    {
        $cashSession->update([
            'closing_balance' => $validated['closing_balance'],
            'closed_at' => now(),
            'status' => 'closed',
            'notes' => $validated['notes'] ?? $cashSession->notes,
        ]);

        $expectedBalance = CashSessionRules::expectedBalance($cashSession);
        $difference = $validated['closing_balance'] - $expectedBalance;

        return [
            'session' => $cashSession,
            'expected_balance' => round($expectedBalance, 2),
            'actual_balance' => round((float) $validated['closing_balance'], 2),
            'difference' => round($difference, 2),
        ];
    }
}
