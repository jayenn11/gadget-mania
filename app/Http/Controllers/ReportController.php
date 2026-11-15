<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $canViewSales = $user->isAdmin() || $user->isKasir();
        $canViewStock = $user->isAdmin() || $user->isGudang();
        $canViewPurchases = $user->isAdmin() || $user->isGudang();

        $allowedTabs = [];
        if ($canViewSales) {
            $allowedTabs[] = 'sales';
        }
        if ($canViewStock) {
            $allowedTabs[] = 'stock';
        }
        if ($canViewPurchases) {
            $allowedTabs[] = 'purchases';
        }

        $requestedTab = (string) $request->query('tab', $allowedTabs[0] ?? 'sales');
        $activeTab = in_array($requestedTab, $allowedTabs, true)
            ? $requestedTab
            : ($allowedTabs[0] ?? 'sales');

        $startDateInput = $request->query('start_date', Carbon::now()->subDays(29)->toDateString());
        $endDateInput = $request->query('end_date', Carbon::now()->toDateString());

        try {
            $startDate = Carbon::parse($startDateInput)->startOfDay();
        } catch (\Throwable) {
            $startDate = Carbon::now()->subDays(29)->startOfDay();
        }

        try {
            $endDate = Carbon::parse($endDateInput)->endOfDay();
        } catch (\Throwable) {
            $endDate = Carbon::now()->endOfDay();
        }

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $categoryId = $request->filled('category_id') ? (int) $request->query('category_id') : null;
        $categories = Category::orderBy('name', 'asc')->get();

        // 1. Sales Trend & Sales Report Data (Admin & Cashier)
        $salesSummary = [
            'total_transactions' => 0,
            'total_units_sold' => 0,
            'total_revenue' => 0.0,
            'estimated_gross_profit' => 0.0,
        ];
        $dailySalesChart = [
            'labels' => [],
            'revenues' => [],
            'units' => [],
            'has_data' => false,
        ];
        $categorySalesChart = [
            'labels' => [],
            'revenues' => [],
            'units' => [],
            'has_data' => false,
        ];
        $topSellingProducts = collect();
        $salesReport = null;

        if ($canViewSales) {
            $salesBaseQuery = StockMovement::with(['product.category', 'user'])
                ->where('type', 'out')
                ->whereBetween('transaction_date', [$startDate, $endDate]);

            if ($categoryId) {
                $salesBaseQuery->whereHas('product', fn ($q) => $q->where('category_id', $categoryId));
            }

            $allSalesMovements = (clone $salesBaseQuery)
                ->orderBy('transaction_date', 'asc')
                ->get();

            $salesSummary['total_transactions'] = $allSalesMovements->count();
            $salesSummary['total_units_sold'] = (int) $allSalesMovements->sum('quantity');
            $salesSummary['total_revenue'] = (float) $allSalesMovements->sum('total_price');
            $salesSummary['estimated_gross_profit'] = (float) $allSalesMovements->sum(function ($movement) {
                $purchaseCost = (float) ($movement->product?->purchase_price ?? 0);
                $sellingUnitPrice = (float) $movement->unit_price;

                return max(0, $sellingUnitPrice - $purchaseCost) * (int) $movement->quantity;
            });

            // Build Daily Sales Trend Series
            $groupedByDate = $allSalesMovements->groupBy(fn ($m) => $m->transaction_date->format('Y-m-d'));
            $daysDiff = $startDate->diffInDays($endDate);

            if ($daysDiff <= 62) {
                foreach (CarbonPeriod::create($startDate->copy()->startOfDay(), '1 day', $endDate->copy()->startOfDay()) as $day) {
                    $dayKey = $day->format('Y-m-d');
                    $dayItems = $groupedByDate->get($dayKey, collect());

                    $dailySalesChart['labels'][] = $day->format('d M');
                    $dailySalesChart['revenues'][] = (float) $dayItems->sum('total_price');
                    $dailySalesChart['units'][] = (int) $dayItems->sum('quantity');
                }
            } else {
                foreach ($groupedByDate as $dayKey => $dayItems) {
                    $dailySalesChart['labels'][] = Carbon::parse($dayKey)->format('d M Y');
                    $dailySalesChart['revenues'][] = (float) $dayItems->sum('total_price');
                    $dailySalesChart['units'][] = (int) $dayItems->sum('quantity');
                }
            }
            $dailySalesChart['has_data'] = $allSalesMovements->isNotEmpty();

            // Build Sales by Category Series
            $groupedByCategory = $allSalesMovements->groupBy(fn ($m) => $m->product?->category?->name ?? 'Uncategorized');
            foreach ($groupedByCategory as $catName => $catItems) {
                $categorySalesChart['labels'][] = $catName;
                $categorySalesChart['revenues'][] = (float) $catItems->sum('total_price');
                $categorySalesChart['units'][] = (int) $catItems->sum('quantity');
            }
            $categorySalesChart['has_data'] = $allSalesMovements->isNotEmpty();

            // Top 5 Selling Products
            $topSellingProducts = $allSalesMovements
                ->groupBy('product_id')
                ->map(function ($items) {
                    $first = $items->first();

                    return [
                        'name' => $first->product?->name ?? 'Unknown Product',
                        'sku' => $first->product?->sku ?? '-',
                        'category' => $first->product?->category?->name ?? '-',
                        'units_sold' => (int) $items->sum('quantity'),
                        'revenue' => (float) $items->sum('total_price'),
                    ];
                })
                ->sortByDesc('revenue')
                ->take(5)
                ->values();

            $salesReport = (clone $salesBaseQuery)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(10, ['*'], 'sales_page')
                ->withQueryString();
        }

        // 2. Current Stock & Inventory Valuation Report (Admin & Warehouse)
        $stockSummary = [
            'total_skus' => 0,
            'total_physical_units' => 0,
            'total_asset_value' => 0.0,
            'potential_retail_value' => 0.0,
            'low_stock_count' => 0,
        ];
        $stockReport = null;

        if ($canViewStock) {
            $stockBaseQuery = Product::with(['category', 'supplier']);
            if ($categoryId) {
                $stockBaseQuery->where('category_id', $categoryId);
            }

            $allMatchingProducts = (clone $stockBaseQuery)->get();
            $stockSummary['total_skus'] = $allMatchingProducts->count();
            $stockSummary['total_physical_units'] = (int) $allMatchingProducts->sum('stock');
            $stockSummary['total_asset_value'] = (float) $allMatchingProducts->sum(fn ($p) => (int) $p->stock * (float) $p->purchase_price);
            $stockSummary['potential_retail_value'] = (float) $allMatchingProducts->sum(fn ($p) => (int) $p->stock * (float) $p->selling_price);
            $stockSummary['low_stock_count'] = $allMatchingProducts->filter(fn ($p) => $p->isLowStock())->count();

            $stockReport = (clone $stockBaseQuery)
                ->orderBy('stock', 'asc')
                ->orderBy('name', 'asc')
                ->paginate(10, ['*'], 'stock_page')
                ->withQueryString();
        }

        // 3. Purchase Order & Procurement Report (Admin & Warehouse)
        $purchaseSummary = [
            'total_pos' => 0,
            'received_pos' => 0,
            'pending_processing_pos' => 0,
            'total_procurement_spend' => 0.0,
        ];
        $purchaseReport = null;

        if ($canViewPurchases) {
            $poBaseQuery = PurchaseOrder::with(['supplier', 'user', 'items.product'])
                ->whereBetween('order_date', [$startDate->toDateString(), $endDate->toDateString()]);

            if ($categoryId) {
                $poBaseQuery->whereHas('items.product', fn ($q) => $q->where('category_id', $categoryId));
            }

            $allMatchingPos = (clone $poBaseQuery)->get();
            $purchaseSummary['total_pos'] = $allMatchingPos->count();
            $purchaseSummary['received_pos'] = $allMatchingPos->filter(fn ($po) => in_array($po->status, ['received', 'diterima'], true))->count();
            $purchaseSummary['pending_processing_pos'] = $allMatchingPos->filter(fn ($po) => in_array($po->status, ['pending', 'processing', 'diproses'], true))->count();
            $purchaseSummary['total_procurement_spend'] = (float) $allMatchingPos
                ->reject(fn ($po) => in_array($po->status, ['cancelled', 'dibatalkan'], true))
                ->sum('total_amount');

            $purchaseReport = (clone $poBaseQuery)
                ->orderBy('order_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(10, ['*'], 'po_page')
                ->withQueryString();
        }

        return view('reports.index', [
            'activeTab' => $activeTab,
            'allowedTabs' => $allowedTabs,
            'canViewSales' => $canViewSales,
            'canViewStock' => $canViewStock,
            'canViewPurchases' => $canViewPurchases,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'categoryId' => $categoryId ? (string) $categoryId : '',
            'categories' => $categories,
            'salesSummary' => $salesSummary,
            'dailySalesChart' => $dailySalesChart,
            'categorySalesChart' => $categorySalesChart,
            'topSellingProducts' => $topSellingProducts,
            'salesReport' => $salesReport,
            'stockSummary' => $stockSummary,
            'stockReport' => $stockReport,
            'purchaseSummary' => $purchaseSummary,
            'purchaseReport' => $purchaseReport,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        $tab = (string) $request->query('tab', $user->isGudang() ? 'stock' : 'sales');

        if ($tab === 'sales' && ! ($user->isAdmin() || $user->isKasir())) {
            abort(403, 'You do not have permission to export the Sales Report.');
        }

        if (in_array($tab, ['stock', 'purchases'], true) && ! ($user->isAdmin() || $user->isGudang())) {
            abort(403, 'You do not have permission to export this report.');
        }

        $startDate = Carbon::parse($request->query('start_date', Carbon::now()->subDays(29)->toDateString()))->startOfDay();
        $endDate = Carbon::parse($request->query('end_date', Carbon::now()->toDateString()))->endOfDay();
        $categoryId = $request->filled('category_id') ? (int) $request->query('category_id') : null;

        $filename = sprintf('gadget-mania-%s-report-%s.csv', $tab, now()->format('Ymd-His'));

        return response()->streamDownload(function () use ($tab, $startDate, $endDate, $categoryId) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for seamless Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            if ($tab === 'stock') {
                fputcsv($handle, [
                    'SKU',
                    'Product Name',
                    'Brand',
                    'Category',
                    'Primary Supplier',
                    'Current Stock',
                    'Minimum Stock',
                    'Unit',
                    'Stock Status',
                    'Purchase Price (Rp)',
                    'Selling Price (Rp)',
                    'Inventory Asset Value (Rp)',
                    'Potential Retail Value (Rp)',
                ]);

                $query = Product::with(['category', 'supplier'])->orderBy('stock', 'asc')->orderBy('name', 'asc');
                if ($categoryId) {
                    $query->where('category_id', $categoryId);
                }

                foreach ($query->get() as $product) {
                    $assetVal = (int) $product->stock * (float) $product->purchase_price;
                    $retailVal = (int) $product->stock * (float) $product->selling_price;

                    fputcsv($handle, [
                        $product->sku,
                        $product->name,
                        $product->brand,
                        $product->category->name ?? '-',
                        $product->supplier->name ?? 'Unassigned',
                        $product->stock,
                        $product->min_stock,
                        $product->unit,
                        $product->isLowStock() ? 'Low Stock' : 'In Stock',
                        number_format((float) $product->purchase_price, 2, '.', ''),
                        number_format((float) $product->selling_price, 2, '.', ''),
                        number_format($assetVal, 2, '.', ''),
                        number_format($retailVal, 2, '.', ''),
                    ]);
                }
            } elseif ($tab === 'purchases') {
                fputcsv($handle, [
                    'PO Number',
                    'Order Date',
                    'Supplier Code',
                    'Supplier Name',
                    'Status',
                    'Line Items Count',
                    'Total Ordered Qty',
                    'Total Amount (Rp)',
                    'Created By',
                    'Notes',
                ]);

                $query = PurchaseOrder::with(['supplier', 'user', 'items.product'])
                    ->whereBetween('order_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orderBy('order_date', 'desc')
                    ->orderBy('id', 'desc');

                if ($categoryId) {
                    $query->whereHas('items.product', fn ($q) => $q->where('category_id', $categoryId));
                }

                foreach ($query->get() as $po) {
                    $statusLabel = match ($po->status) {
                        'diproses', 'processing' => 'Processing',
                        'diterima', 'received' => 'Received',
                        'dibatalkan', 'cancelled' => 'Cancelled',
                        default => 'Pending',
                    };

                    fputcsv($handle, [
                        $po->po_number,
                        $po->order_date?->format('Y-m-d'),
                        $po->supplier->code ?? '-',
                        $po->supplier->name ?? '-',
                        $statusLabel,
                        $po->items->count(),
                        $po->items->sum('quantity'),
                        number_format((float) $po->total_amount, 2, '.', ''),
                        $po->user->name ?? '-',
                        $po->notes ?? '',
                    ]);
                }
            } else {
                fputcsv($handle, [
                    'Reference Code',
                    'Transaction Date',
                    'SKU',
                    'Product Name',
                    'Category',
                    'Quantity Sold',
                    'Unit Selling Price (Rp)',
                    'Total Revenue (Rp)',
                    'Estimated Gross Profit (Rp)',
                    'Cashier / Recorded By',
                    'Notes',
                ]);

                $query = StockMovement::with(['product.category', 'user'])
                    ->where('type', 'out')
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->orderBy('transaction_date', 'desc')
                    ->orderBy('id', 'desc');

                if ($categoryId) {
                    $query->whereHas('product', fn ($q) => $q->where('category_id', $categoryId));
                }

                foreach ($query->get() as $sale) {
                    $purchaseCost = (float) ($sale->product?->purchase_price ?? 0);
                    $profit = max(0, (float) $sale->unit_price - $purchaseCost) * (int) $sale->quantity;

                    fputcsv($handle, [
                        $sale->reference_code,
                        $sale->transaction_date?->format('Y-m-d H:i:s'),
                        $sale->product->sku ?? '-',
                        $sale->product->name ?? '-',
                        $sale->product->category->name ?? '-',
                        $sale->quantity,
                        number_format((float) $sale->unit_price, 2, '.', ''),
                        number_format((float) $sale->total_price, 2, '.', ''),
                        number_format($profit, 2, '.', ''),
                        $sale->user->name ?? '-',
                        $sale->notes ?? '',
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
