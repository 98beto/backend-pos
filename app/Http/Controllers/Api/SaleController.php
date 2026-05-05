<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\CashSession;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Support\CashSessionRules;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $normalizedSearch = $request->search
            ? Str::of($request->search)->ascii()->lower()->value()
            : null;

        $sales = Sale::with("customer", "branch")
            ->when($request->search, function ($q, $search) use ($normalizedSearch) {
                $q->where(function ($q) use ($search, $normalizedSearch) {
                    if (is_numeric($search)) {
                        $q->where("id", $search);
                    }

                    $q->orWhereHas("customer", fn($q) => $q->where("name", "ILIKE", "%{$search}%"));

                    if (str_contains($normalizedSearch, 'publico general')) {
                        $q->orWhereNull('customer_id');
                    }
                });
            })
            ->when(
                $request->date_from,
                fn($q, $date) => $q->whereDate("sale_date", ">=", $date),
            )
            ->when(
                $request->date_to,
                fn($q, $date) => $q->whereDate("sale_date", "<=", $date),
            )
            ->when(
                $request->cash_session_id,
                fn($q, $id) => $q->where("cash_session_id", $id),
            )
            ->when(
                $request->branch_id,
                fn($q, $id) => $q->where("branch_id", $id),
            )
            ->when(
                $request->payment_method,
                fn($q, $method) => $q->where("payment_method", $method),
            )
            ->when(
                $request->customer_id,
                fn($q, $id) => $q->where("customer_id", $id),
            )
            ->when(
                $request->status,
                fn($q, $status) => $q->where("status", $status),
            )
            ->latest()
            ->paginate(20);

        $resource = SaleResource::collection($sales)
            ->response()
            ->getData(true);

        return response()->json([
            "success" => true,
            "data" => $resource,
        ]);
    }

    /**
     * Process and store a new sale.
     *
     * Expected payload:
     * {
     *   "customer_id": 1,           // optional
     *   "cash_session_id": 1,       // required
     *   "payment_method": "cash",   // required
     *   "discount_amount": 0,       // optional, default 0
     *   "items": [                  // required, at least 1 item
     *     {
     *       "product_id": 3,
     *       "quantity": 2,
     *       "unit_price": 25.00,
     *       "tax_amount": 4.00,
     *       "subtotal": 50.00,
     *       "total": 54.00
     *     }
     *   ]
     * }
     */
    public function store(StoreSaleRequest $request)
    {
        $validated = $request->validated();

        try {
            $sale = DB::transaction(function () use ($validated) {
                $cashSession = CashSession::lockForUpdate()->findOrFail($validated['cash_session_id']);

                CashSessionRules::ensureCashSessionIsOpen($cashSession);
                CashSessionRules::ensureCashSessionBelongsToBranch($cashSession, (int) $validated['branch_id']);

                // 1. Validate stock availability for all items before doing anything
                foreach ($validated["items"] as $item) {
                    $product = Product::lockForUpdate()->find(
                        $item["product_id"],
                    );

                    if ($product->stock_quantity < $item["quantity"]) {
                        throw ValidationException::withMessages([
                            "items" =>
                                "Insufficient stock for product: \"{$product->name}\". " .
                                "Available: {$product->stock_quantity}, Requested: {$item["quantity"]}.",
                        ]);
                    }
                }

                // 2. Calculate sale totals from items (never trust frontend totals)
                $subtotal = collect($validated["items"])->sum("subtotal");
                $taxAmount = collect($validated["items"])->sum("tax_amount");
                $discount = $validated["discount_amount"] ?? 0;
                $total = $subtotal + $taxAmount - $discount;

                // 3. Create the sale header
                $sale = Sale::create([
                    'customer_id'     => $validated['customer_id'] ?? null,
                    'cash_session_id' => $validated['cash_session_id'],
                    'branch_id'       => $validated['branch_id'],
                    'payment_method'  => $validated['payment_method'],
                    'subtotal'        => $subtotal,
                    'tax_amount'      => $taxAmount,
                    'discount_amount' => $discount,
                    'total_amount'    => $total,
                    'status'          => 'completed',
                    'sale_date'       => now(),
                ]);

                // 4. Create each sale detail and deduct stock
                foreach ($validated["items"] as $item) {
                    // Store historical price — never re-read the live product price
                    $sale->saleDetails()->create([
                        "product_id" => $item["product_id"],
                        "quantity" => $item["quantity"],
                        "unit_price" => $item["unit_price"],
                        "tax_amount" => $item["tax_amount"],
                        "subtotal" => $item["subtotal"],
                        "total" => $item["total"],
                    ]);

                    // Atomic stock decrement
                    Product::where("id", $item["product_id"])->decrement(
                        "stock_quantity",
                        $item["quantity"],
                    );

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

            $sale->load("customer", "cashSession", "saleDetails.product", "branch");

            return response()->json(
                [
                    "success" => true,
                    "message" => "Sale processed successfully.",
                    "data" => new SaleResource($sale),
                ],
                201,
            );
        } catch (ValidationException $e) {
            // Known business rule violation (e.g. insufficient stock)
            return response()->json(
                [
                    "success" => false,
                    "message" => $e->getMessage(),
                    "errors" => $e->errors(),
                ],
                422,
            );
        } catch (\Throwable $e) {
            // Unexpected errors — do not expose internal details
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "An unexpected error occurred while processing the sale.",
                ],
                500,
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale)
    {
        $sale->load("customer", "cashSession", "saleDetails.product", "branch");

        return response()->json([
            "success" => true,
            "data" => new SaleResource($sale),
        ]);
    }
}
