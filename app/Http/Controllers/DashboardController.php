<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Sale;
use App\Models\WarehouseStock;
use App\Models\StoreStock;
use App\Models\Supplier;
use App\Models\SaleDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Admin melihat semua cabang.
        // Kasir hanya melihat cabangnya sendiri.
        $salesQuery = Sale::query();
        $storeStockQuery = StoreStock::query();

        if ($user->role !== 'admin') {
            $salesQuery->where('branch_id', $user->branch_id);
            $storeStockQuery->where('branch_id', $user->branch_id);
        }

        $todayIncome = (clone $salesQuery)
            ->whereDate('sale_date', Carbon::today())
            ->sum('grand_total');

        $monthIncome = (clone $salesQuery)
            ->whereMonth('sale_date', Carbon::now()->month)
            ->whereYear('sale_date', Carbon::now()->year)
            ->sum('grand_total');

        $todaySales = (clone $salesQuery)
            ->whereDate('sale_date', Carbon::today())
            ->count();

        $lowStockQuery = StoreStock::with('product', 'branch');

        if ($user->role !== 'admin') {
            $lowStockQuery->where('branch_id', $user->branch_id);
        }

        $lowStockProducts = $lowStockQuery
            ->get()
            ->filter(function ($stock) {
                return $stock->product &&
                    $stock->qty <= $stock->product->minimum_stock;
            })
            ->values();

        $lowStock = $lowStockProducts->count();

        $topProducts = SaleDetail::select(
                'product_id',
                DB::raw('SUM(qty) as total_sold')
            )
            ->when($user->role !== 'admin', function ($query) use ($user) {
                $query->whereHas('sale', function ($saleQuery) use ($user) {
                    $saleQuery->where('branch_id', $user->branch_id);
                });
            })
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        $latestTransactions = Sale::with('user', 'branch')
            ->when($user->role !== 'admin', function ($query) use ($user) {
                $query->where('branch_id', $user->branch_id);
            })
            ->latest()
            ->take(5)
            ->get();

        $today = Carbon::today();

        $expiredProducts = Product::whereDate(
            'expired_date',
            '<',
            $today
        )->count();

        $warningProducts = Product::whereBetween(
            'expired_date',
            [$today, $today->copy()->addDays(30)]
        )->count();

        $monthSales = (clone $salesQuery)
            ->whereMonth('sale_date', Carbon::now()->month)
            ->whereYear('sale_date', Carbon::now()->year)
            ->count();

        return response()->json([
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'total_suppliers' => Supplier::count(),
            'warehouse_stock' => WarehouseStock::sum('qty'),

            'store_stock' => (clone $storeStockQuery)->sum('qty'),
            'total_sales' => (clone $salesQuery)->count(),

            'today_sales' => $todaySales,
            'today_income' => $todayIncome,
            'month_income' => $monthIncome,

            'low_stock' => $lowStock,
            'low_stock_products' => $lowStockProducts,

            'top_products' => $topProducts,
            'latest_transactions' => $latestTransactions,

            'expired_products' => $expiredProducts,
            'warning_products' => $warningProducts,
            'month_sales' => $monthSales
        ]);
    }
}