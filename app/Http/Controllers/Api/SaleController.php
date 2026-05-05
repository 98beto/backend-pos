<?php

namespace App\Http\Controllers\Api;

use App\Actions\Sales\ProcessSale;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use Illuminate\Support\Str;
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
    public function store(StoreSaleRequest $request, ProcessSale $processSale)
    {
        $validated = $request->validated();

        try {
            $sale = $processSale->handle($validated);

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
