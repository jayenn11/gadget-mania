<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Inventory Dashboard</h1>
            <p class="text-xs text-slate-500">Real-time stock overview and operational metrics for Gadget Mania</p>
        </div>
    </x-slot>

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        <!-- KPI 1: Total Products & Stock Units -->
        <div class="bg-white border border-[#E2E8F0] rounded-2xl p-5 shadow-xs flex items-start justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Product Catalog</span>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-slate-900">{{ number_format($totalProducts, 0, ',', '.') }}</span>
                    <span class="text-xs font-medium text-slate-500">Active SKUs</span>
                </div>
                <p class="mt-2 text-xs text-slate-600">
                    Total Available Stock: <span class="font-bold text-[#2563EB]">{{ number_format($totalStockUnits, 0, ',', '.') }} Units</span>
                </p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-[#2563EB] flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
        </div>

        <!-- KPI 2: Low Stock Alert -->
        <div class="bg-white border {{ $lowStockCount > 0 ? 'border-rose-200 ring-1 ring-rose-100' : 'border-[#E2E8F0]' }} rounded-2xl p-5 shadow-xs flex items-start justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider {{ $lowStockCount > 0 ? 'text-[#E11D48]' : 'text-slate-500' }}">Low Stock Alert</span>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold {{ $lowStockCount > 0 ? 'text-[#E11D48]' : 'text-[#059669]' }}">
                        {{ number_format($lowStockCount, 0, ',', '.') }}
                    </span>
                    <span class="text-xs font-medium text-slate-500">Products</span>
                </div>
                <p class="mt-2 text-xs text-slate-600">
                    @if($lowStockCount > 0)
                        <span class="font-semibold text-[#D97706]">Immediate restock / PO required</span>
                    @else
                        <span class="font-semibold text-[#059669]">All stock levels are healthy</span>
                    @endif
                </p>
            </div>
            <div class="w-11 h-11 rounded-xl {{ $lowStockCount > 0 ? 'bg-rose-50 text-[#E11D48]' : 'bg-emerald-50 text-[#059669]' }} flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>

        <!-- KPI 3: Active Suppliers -->
        <div class="bg-white border border-[#E2E8F0] rounded-2xl p-5 shadow-xs flex items-start justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Suppliers</span>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-slate-900">{{ number_format($totalSuppliers, 0, ',', '.') }}</span>
                    <span class="text-xs font-medium text-slate-500">Distributors</span>
                </div>
                <p class="mt-2 text-xs text-slate-600">
                    Authorized electronics partners
                </p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-[#059669] flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
        </div>

        <!-- KPI 4: Monthly Transactions -->
        <div class="bg-white border border-[#E2E8F0] rounded-2xl p-5 shadow-xs flex items-start justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Monthly Transactions</span>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-slate-900">{{ number_format($totalMonthlyTransactions, 0, ',', '.') }}</span>
                    <span class="text-xs font-medium text-slate-500">Movements</span>
                </div>
                <div class="mt-2 flex items-center gap-3 text-xs">
                    <span class="text-[#059669] font-semibold">&uarr; {{ $monthlyInCount }} Stock In</span>
                    <span class="text-slate-300">|</span>
                    <span class="text-[#2563EB] font-semibold">&darr; {{ $monthlyOutCount }} Sold</span>
                </div>
            </div>
            <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
            </div>
        </div>
    </div>

    <!-- 7-Day Daily Sales Trend Analytics Summary Card -->
    <div class="bg-white border border-[#E2E8F0] rounded-2xl p-6 shadow-xs"
         x-data="{
            labels: @js($sevenDayLabels),
            revenues: @js($sevenDayRevenue),
            units: @js($sevenDayUnits),
            init() {
                this.$nextTick(() => {
                    const canvas = this.$refs.dashboardSalesChart;
                    if (!canvas || !window.Chart) return;
                    new window.Chart(canvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: this.labels,
                            datasets: [
                                {
                                    type: 'line',
                                    label: 'Daily Revenue (Rp)',
                                    data: this.revenues,
                                    borderColor: '#2563EB',
                                    backgroundColor: 'rgba(37, 99, 235, 0.12)',
                                    borderWidth: 2.5,
                                    pointBackgroundColor: '#2563EB',
                                    pointRadius: 4,
                                    tension: 0.35,
                                    fill: true,
                                    yAxisID: 'yRevenue',
                                },
                                {
                                    type: 'bar',
                                    label: 'Units Sold',
                                    data: this.units,
                                    backgroundColor: 'rgba(5, 150, 105, 0.75)',
                                    borderRadius: 6,
                                    maxBarThickness: 28,
                                    yAxisID: 'yUnits',
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: { usePointStyle: true, boxWidth: 8, font: { size: 12 } }
                                },
                                tooltip: {
                                    callbacks: {
                                        label(context) {
                                            if (context.dataset.yAxisID === 'yRevenue') {
                                                return ' Revenue: Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y || 0);
                                            }
                                            return ' Sold: ' + (context.parsed.y || 0) + ' Units';
                                        }
                                    }
                                }
                            },
                            scales: {
                                yRevenue: {
                                    type: 'linear',
                                    position: 'left',
                                    beginAtZero: true,
                                    grid: { color: '#F1F5F9' },
                                    ticks: {
                                        font: { size: 11 },
                                        callback(value) {
                                            if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                                            if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + 'K';
                                            return 'Rp ' + value;
                                        }
                                    }
                                },
                                yUnits: {
                                    type: 'linear',
                                    position: 'right',
                                    beginAtZero: true,
                                    grid: { drawOnChartArea: false },
                                    ticks: { precision: 0, font: { size: 11 } }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 11 } }
                                }
                            }
                        }
                    });
                });
            }
         }">
        <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <h2 class="text-sm font-bold text-slate-900">7-Day Sales Revenue & Volume Trend</h2>
                <p class="text-xs text-slate-500">Daily retail revenue and units sold over the last 7 days</p>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <div class="text-right">
                    <span class="block text-[11px] uppercase tracking-wider font-semibold text-slate-400">7-Day Revenue</span>
                    <span class="text-sm font-bold text-[#2563EB]">Rp {{ number_format((float) $sevenDayTotalRevenue, 0, ',', '.') }}</span>
                </div>
                <div class="h-7 w-px bg-slate-200"></div>
                <div class="text-right">
                    <span class="block text-[11px] uppercase tracking-wider font-semibold text-slate-400">7-Day Volume</span>
                    <span class="text-sm font-bold text-[#059669]">{{ number_format((int) $sevenDayTotalUnits, 0, ',', '.') }} Units</span>
                </div>
                <a href="{{ route('reports.index') }}"
                   class="px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-[#2563EB] text-xs font-semibold transition">
                    Full Analytics &rarr;
                </a>
            </div>
        </div>
        <div class="mt-4 h-64 relative">
            <canvas x-ref="dashboardSalesChart"></canvas>
        </div>
    </div>

    <!-- Main Dashboard Grid: Low Stock Alert Table + Recent Stock Movements -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Low Stock Alert Table (7 Columns) -->
        <div class="lg:col-span-7 bg-white border border-[#E2E8F0] rounded-2xl shadow-xs overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-[#E2E8F0] flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full {{ $lowStockCount > 0 ? 'bg-[#E11D48]' : 'bg-[#059669]' }}"></span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Low Stock Alert</h2>
                        <p class="text-xs text-slate-500">Products with current stock &le; minimum threshold</p>
                    </div>
                </div>
                <a href="{{ route('products.index', ['stock_status' => 'low']) }}" class="text-xs font-semibold text-[#2563EB] hover:underline">
                    View All &rarr;
                </a>
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider border-b border-[#E2E8F0]">
                            <th class="py-3 px-4 font-semibold">Product & SKU</th>
                            <th class="py-3 px-4 font-semibold">Category</th>
                            <th class="py-3 px-4 font-semibold text-center">Current Stock</th>
                            <th class="py-3 px-4 font-semibold text-center">Min. Stock</th>
                            @if(auth()->user()->isAdmin() || auth()->user()->isGudang())
                                <th class="py-3 px-4 font-semibold text-right">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($lowStockProducts as $product)
                            <tr class="hover:bg-rose-50/30 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-900">{{ $product->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $product->sku }} &bull; {{ $product->brand }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600">
                                    {{ $product->category->name ?? '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-[#E11D48]">
                                        {{ $product->stock }} {{ $product->unit }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center text-xs font-semibold text-slate-600 whitespace-nowrap">
                                    {{ $product->min_stock }} {{ $product->unit }}
                                </td>
                                @if(auth()->user()->isAdmin() || auth()->user()->isGudang())
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <a href="{{ route('purchase-orders.index', ['create' => 1, 'product_id' => $product->id, 'supplier_id' => $product->supplier_id]) }}"
                                           class="whitespace-nowrap shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#2563EB] hover:bg-blue-700 text-white text-xs font-semibold shadow-2xs transition">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                            </svg>
                                            <span>Create PO</span>
                                        </a>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 px-4 text-center text-sm text-slate-500">
                                    All products are currently above minimum stock thresholds.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Stock Activity Widget (5 Columns) -->
        <div class="lg:col-span-5 bg-white border border-[#E2E8F0] rounded-2xl shadow-xs overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-[#E2E8F0] flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Recent Stock Activity</h2>
                    <p class="text-xs text-slate-500">Latest 5 inventory movements</p>
                </div>
                <a href="{{ route('stock-movements.index') }}" class="text-xs font-semibold text-[#2563EB] hover:underline">
                    View All &rarr;
                </a>
            </div>

            <div class="divide-y divide-slate-100 flex-1">
                @forelse($recentMovements as $movement)
                    <div class="px-6 py-3.5 flex items-start justify-between gap-3 hover:bg-slate-50/70 transition">
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="mt-0.5 w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $movement->type === 'in' ? 'bg-emerald-100 text-[#059669]' : 'bg-blue-100 text-[#2563EB]' }}">
                                @if($movement->type === 'in')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                    </svg>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-900 truncate">
                                    {{ $movement->product->name ?? 'Deleted Product' }}
                                </p>
                                <p class="text-[11px] text-slate-500 truncate mt-0.5">
                                    {{ $movement->reference_code }} &bull; By {{ $movement->user->name ?? '-' }}
                                </p>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    {{ $movement->transaction_date?->format('d M Y, H:i') }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $movement->type === 'in' ? 'bg-emerald-50 text-[#059669]' : 'bg-blue-50 text-[#2563EB]' }}">
                                {{ $movement->type === 'in' ? '+' : '-' }}{{ $movement->quantity }}
                            </span>
                            <div class="text-[11px] font-medium text-slate-500 mt-1">
                                Rp {{ number_format((float) $movement->total_price, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-slate-500">
                        No stock movements recorded yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
