<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalProducts = Product::count();
        $totalStockUnits = (int) Product::sum('stock');
        $lowStockCount = Product::lowStock()->count();
        $totalSuppliers = Supplier::count();

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthlyInCount = StockMovement::where('type', 'in')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->count();

        $monthlyOutCount = StockMovement::where('type', 'out')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->count();

        $totalMonthlyTransactions = $monthlyInCount + $monthlyOutCount;

        // 7-Day Daily Sales Trend (Last 7 days inclusive)
        $sevenDaysAgo = Carbon::now()->subDays(6)->startOfDay();
        $todayEnd = Carbon::now()->endOfDay();

        $recentSales = StockMovement::where('type', 'out')
            ->whereBetween('transaction_date', [$sevenDaysAgo, $todayEnd])
            ->get();

        $salesByDay = $recentSales->groupBy(fn ($item) => $item->transaction_date->format('Y-m-d'));

        $sevenDayLabels = [];
        $sevenDayRevenue = [];
        $sevenDayUnits = [];

        foreach (CarbonPeriod::create($sevenDaysAgo, '1 day', $todayEnd) as $date) {
            $key = $date->format('Y-m-d');
            $dayGroup = $salesByDay->get($key, collect());

            $sevenDayLabels[] = $date->format('d M');
            $sevenDayRevenue[] = (float) $dayGroup->sum('total_price');
            $sevenDayUnits[] = (int) $dayGroup->sum('quantity');
        }

        $sevenDayTotalRevenue = array_sum($sevenDayRevenue);
        $sevenDayTotalUnits = array_sum($sevenDayUnits);

        $lowStockProducts = Product::with(['category', 'supplier'])
            ->lowStock()
            ->orderBy('stock', 'asc')
            ->get();

        $recentMovements = StockMovement::with(['product', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalProducts',
            'totalStockUnits',
            'lowStockCount',
            'totalSuppliers',
            'totalMonthlyTransactions',
            'monthlyInCount',
            'monthlyOutCount',
            'sevenDayLabels',
            'sevenDayRevenue',
            'sevenDayUnits',
            'sevenDayTotalRevenue',
            'sevenDayTotalUnits',
            'lowStockProducts',
            'recentMovements'
        ));
    }
}
