<?php

namespace App\Actions\Sales;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Support\CashSessionRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcessSale
{
    public function handle(array $validated): Sale
    {
        return DB::transaction(function () use ($validated) {
            $cashSession = CashSession::lockForUpdate()->findOrFail($validated['cash_session_id']);

            CashSessionRules::ensureCashSessionIsOpen($cashSession);
            CashSessionRules::ensureCashSessionBelongsToBranch($cashSession, (int) $validated['branch_id']);

            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock_quantity < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => 'Insufficient stock for product: "'.$product->name.'". '
                            .'Available: '.$product->stock_quantity.', Requested: '.$item['quantity'].'.',
                    ]);
                }
            }

            $subtotal = collect($validated['items'])->sum('subtotal');
            $taxAmount = collect($validated['items'])->sum('tax_amount');
            $discount = $validated['discount_amount'] ?? 0;
            $total = $subtotal + $taxAmount - $discount;

            $sale = Sale::create([
                'customer_id' => $validated['customer_id'] ?? null,
                'cash_session_id' => $validated['cash_session_id'],
                'branch_id' => $validated['branch_id'],
                'payment_method' => $validated['payment_method'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'status' => 'completed',
                'sale_date' => now(),
            ]);

            foreach ($validated['items'] as $item) {
                $sale->saleDetails()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_amount' => $item['tax_amount'],
                    'subtotal' => $item['subtotal'],
                    'total' => $item['total'],
                ]);

                Product::where('id', $item['product_id'])->decrement('stock_quantity', $item['quantity']);

                InventoryMovement::create([
                    'product_id' => $item['product_id'],
                    'branch_id' => $sale->branch_id,
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'source' => 'sale',
                    'reference_id' => $sale->id,
                    'notes' => "Sale #{$sale->id}",
                ]);
            }

            if ($sale->payment_method === 'cash') {
                CashMovement::create([
                    'cash_session_id' => $sale->cash_session_id,
                    'branch_id' => $sale->branch_id,
                    'type' => 'in',
                    'category' => 'sale',
                    'amount' => $sale->total_amount,
                    'source' => 'sale',
                    'reference_id' => $sale->id,
                    'notes' => "Sale #{$sale->id}",
                ]);
            }

            return $sale;
        });
    }
}
