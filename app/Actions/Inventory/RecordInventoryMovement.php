<?php

namespace App\Actions\Inventory;

use App\Models\BranchProduct;
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
            $branchProduct = BranchProduct::query()
                ->where('branch_id', $validated['branch_id'])
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $branchProduct) {
                throw ValidationException::withMessages([
                    'product_id' => 'The selected product is not available in this branch.',
                ]);
            }

            if ($validated['type'] === 'out' && $branchProduct->stock_quantity < $validated['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock for "'.$product->name.'". '
                        .'Available: '.$branchProduct->stock_quantity.', Requested: '.$validated['quantity'].'.',
                ]);
            }

            match ($validated['type']) {
                'in' => $branchProduct->increment('stock_quantity', $validated['quantity']),
                'out' => $branchProduct->decrement('stock_quantity', $validated['quantity']),
                'adjustment' => $branchProduct->update(['stock_quantity' => $validated['quantity']]),
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
