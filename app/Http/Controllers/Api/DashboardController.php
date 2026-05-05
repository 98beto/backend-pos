<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Return a summary of key business metrics.
     *
     * Response shape:
     * {
     *   "today": {
     *     "sales_count":   int,
     *     "revenue":       float,   // sum of total_amount for today's completed sales
     *     "items_sold":    int      // sum of quantities across all today's sale details
     *   },
     *   "cash_session": {           // null when no session is currently open
     *     "id":             int,
     *     "opened_at":      string,
     *     "opening_balance":float
     *   },
     *   "inventory": {
     *     "total_products":     int,
     *     "active_products":    int,
     *     "low_stock_count":    int  // products where stock_quantity <= min_stock
     *   }
     * }
     */
    public function summary(Request $request)
    {
        // ── Today's sales ──────────────────────────────────────────────────────
        $todaySales = Sale::whereDate('sale_date', today())
            ->where('status', 'completed')
            ->when($request->branch_id, fn ($q, $branchId) => $q->where('branch_id', $branchId));

        $todaySalesCount = (clone $todaySales)->count();
        $todayRevenue    = (clone $todaySales)->sum('total_amount');

        $todayItemsSold  = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->whereDate('sales.sale_date', today())
            ->where('sales.status', 'completed')
            ->when($request->branch_id, fn ($q, $branchId) => $q->where('sales.branch_id', $branchId))
            ->sum('sale_details.quantity');

        // ── Open cash session ──────────────────────────────────────────────────
        $openSession = CashSession::where('status', 'open')
            ->when($request->branch_id, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->latest('opened_at')
            ->first();

        // ── Inventory snapshot ─────────────────────────────────────────────────
        $totalProducts  = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $lowStockCount  = Product::lowStock()->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'today'        => [
                    'sales_count' => $todaySalesCount,
                    'revenue'     => round((float) $todayRevenue, 2),
                    'items_sold'  => (int) $todayItemsSold,
                ],
                'cash_session' => $openSession ? [
                    'id'              => $openSession->id,
                    'opened_at'       => $openSession->opened_at?->toDateTimeString(),
                    'opening_balance' => (float) $openSession->opening_balance,
                ] : null,
                'inventory'    => [
                    'total_products'  => $totalProducts,
                    'active_products' => $activeProducts,
                    'low_stock_count' => $lowStockCount,
                ],
            ],
        ]);
    }
}
