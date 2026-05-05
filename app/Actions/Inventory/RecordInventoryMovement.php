<?php

namespace App\Actions\Inventory;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordInventoryMovement
{
    public function handle(array $validated): InventoryMovement
    {
        return DB::transaction(function () use ($validated) {
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);

            if ($validated['type'] === 'out' && $product->stock_quantity < $validated['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock for "'.$product->name.'". '
                        .'Available: '.$product->stock_quantity.', Requested: '.$validated['quantity'].'.',
                ]);
            }

            match ($validated['type']) {
                'in' => $product->increment('stock_quantity', $validated['quantity']),
                'out' => $product->decrement('stock_quantity', $validated['quantity']),
                'adjustment' => $product->update(['stock_quantity' => $validated['quantity']]),
            };

            return InventoryMovement::create([
                'product_id' => $validated['product_id'],
                'branch_id' => $validated['branch_id'],
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'source' => 'manual',
                'reference_id' => null,
                'notes' => $validated['notes'] ?? null,
            ]);
        });
    }
}
