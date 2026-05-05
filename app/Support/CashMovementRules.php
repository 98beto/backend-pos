<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class CashMovementRules
{
    public static function ensureManualCategoryAllowed(string $category): void
    {
        if ($category === 'sale') {
            throw ValidationException::withMessages([
                'category' => 'The sale category is reserved for automatic sale movements.',
            ]);
        }
    }

    public static function ensureAdjustmentHasNotes(string $category, ?string $notes): void
    {
        if ($category === 'adjustment' && blank($notes)) {
            throw ValidationException::withMessages([
                'notes' => 'Notes are required when recording an adjustment.',
            ]);
        }
    }
}
