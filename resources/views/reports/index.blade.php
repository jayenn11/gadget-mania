<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <div>
                <h1 class="text-lg font-bold text-slate-900">Reports & Sales Trend Analytics</h1>
                <p class="text-xs text-slate-500">Enterprise financial valuation, sales trend visualization, and procurement audit reports</p>
            </div>
        </div>
    </x-slot>

    @php
        $categoriesCatalog = $categories->map(fn ($c) => [
            'id' => (string) $c->id,
            'name' => $c->name,
        ])->values();
    @endphp

    <div x-data="{
            activeTab: @js($activeTab),
            start_date: @js($startDate),
            end_date: @js($endDate),
            category_id: @js($categoryId),
            categoriesCatalog: @js($categoriesCatalog),
            baseUrl: @js(route('reports.index')),
            exportBaseUrl: @js(route('reports.export-csv')),
            dailyChartData: @js($dailySalesChart),
            categoryChartData: @js($categorySalesChart),
            dailyChartInstance: null,
            categoryChartInstance: null,
            init() {
                this.$nextTick(() => {
                    this.renderCharts();
                });
            },
            switchTab(tab) {
                this.activeTab = tab;
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url.toString());
                if (tab === 'sales') {
                    this.$nextTick(() => this.renderCharts());
                }
            },
            findCategory(catId) {
                return this.categoriesCatalog.find(c => c.id === String(catId || '')) || null;
            },
            filterCategories(q = '') {
                const query = (q || '').toLowerCase().trim();
                if (!query) return this.categoriesCatalog;
                return this.categoriesCatalog.filter(c => c.name.toLowerCase().includes(query));
            },
            get exportCsvUrl() {
                const url = new URL(this.exportBaseUrl, window.location.origin);
                url.searchParams.set('tab', this.activeTab);
                if (this.start_date) url.searchParams.set('start_date', this.start_date);
                if (this.end_date) url.searchParams.set('end_date', this.end_date);
                if (this.category_id) url.searchParams.set('category_id', this.category_id);
                return url.toString();
            },
            get activeReportTitle() {
                if (this.activeTab === 'stock') return 'Current Stock & Inventory Valuation Report';
                if (this.activeTab === 'purchases') return 'Purchase Order & Procurement Report';
                return 'Sales Trend & Retail Sales Report';
            },
            submitFilters() {
                this.$refs.reportFilterForm.submit();
            },
            resetFilters() {
                this.start_date = '{{ now()->subDays(29)->toDateString() }}';
                this.end_date = '{{ now()->toDateString() }}';
                this.category_id = '';
                this.$nextTick(() => this.submitFilters());
            },
            renderCharts() {
                if (!window.Chart) return;

                if (this.$refs.dailySalesCanvas && this.dailyChartData.has_data) {
                    if (this.dailyChartInstance) {
                        this.dailyChartInstance.destroy();
                    }
                    this.dailyChartInstance = new window.Chart(this.$refs.dailySalesCanvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: this.dailyChartData.labels,
                            datasets: [
                                {
                                    type: 'line',
                                    label: 'Sales Revenue (Rp)',
                                    data: this.dailyChartData.revenues,
                                    borderColor: '#2563EB',
                                    backgroundColor: 'rgba(37, 99, 235, 0.12)',
                                    borderWidth: 2.5,
                                    pointBackgroundColor: '#2563EB',
                                    pointRadius: 3.5,
                                    tension: 0.35,
                                    fill: true,
                                    yAxisID: 'yRevenue',
                                },
                                {
                                    type: 'bar',
                                    label: 'Units Sold',
                                    data: this.dailyChartData.units,
                                    backgroundColor: 'rgba(5, 150, 105, 0.75)',
                                    borderRadius: 6,
                                    maxBarThickness: 24,
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
                                            return ' Units Sold: ' + (context.parsed.y || 0) + ' Units';
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
                                    ticks: { font: { size: 11 }, maxRotation: 45 }
                                }
                            }
                        }
                    });
                }

                if (this.$refs.categorySalesCanvas && this.categoryChartData.has_data) {
                    if (this.categoryChartInstance) {
                        this.categoryChartInstance.destroy();
                    }
                    this.categoryChartInstance = new window.Chart(this.$refs.categorySalesCanvas.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: this.categoryChartData.labels,
                            datasets: [
                                {
                                    label: 'Revenue by Category (Rp)',
                                    data: this.categoryChartData.revenues,
                                    backgroundColor: [
                                        '#2563EB',
                                        '#059669',
                                        '#D97706',
                                        '#7C3AED',
                                        '#E11D48',
                                        '#0891B2'
                                    ],
                                    borderWidth: 2,
                                    borderColor: '#FFFFFF',
                                    hoverOffset: 6
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '64%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { usePointStyle: true, boxWidth: 8, padding: 14, font: { size: 11 } }
                                },
                                tooltip: {
                                    callbacks: {
                                        label(context) {
                                            const val = context.parsed || 0;
                                            return ' ' + context.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(val);
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }"
        class="space-y-6 print:px-10 print:py-8">

        <style>
            @media print {
                @page {
                    margin: 0;
                }
            }
        </style>

        <!-- Formal Print Header (Visible only when printing) -->
        <div class="print-only border-b-2 border-slate-900 pb-4 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Gadget Mania — Inventory & Financial Report</h1>
                    <p class="text-sm font-semibold text-slate-700 mt-0.5" x-text="activeReportTitle"></p>
                    <p class="text-xs text-slate-500 mt-1">
                        Period: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                        &bull; Category: <span x-text="findCategory(category_id) ? findCategory(category_id).name : 'All Categories'"></span>
                    </p>
                </div>
                <div class="text-right text-xs text-slate-500">
                    <p>Generated on: {{ now()->format('d M Y, H:i') }}</p>
                    <p>Generated by: {{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})</p>
                </div>
            </div>
        </div>

        <!-- Filter & Export Bar (Hidden when printing) -->
        <form x-ref="reportFilterForm" method="GET" action="{{ route('reports.index') }}"
              class="no-print bg-white border border-[#E2E8F0] rounded-2xl p-4 sm:p-5 shadow-xs">
            <input type="hidden" name="tab" :value="activeTab">
            <input type="hidden" name="category_id" :value="category_id">

            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <!-- Start Date -->
                    <div class="sm:col-span-3">
                        <label for="report_start_date" class="block text-xs font-semibold text-slate-600 mb-1">
                            Start Date
                        </label>
                        <input type="date" id="report_start_date" name="start_date"
                               x-model="start_date"
                               @change="submitFilters()"
                               class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <!-- End Date -->
                    <div class="sm:col-span-3">
                        <label for="report_end_date" class="block text-xs font-semibold text-slate-600 mb-1">
                            End Date
                        </label>
                        <input type="date" id="report_end_date" name="end_date"
                               x-model="end_date"
                               @change="submitFilters()"
                               class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <!-- Category Custom Searchable Dropdown -->
                    <div class="sm:col-span-4" x-data="{ open: false, q: '' }">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Product Category
                        </label>
                        <div class="relative">
                            <button type="button"
                                    @click="open = !open; if (open) $nextTick(() => $refs.reportCatInput.focus())"
                                    class="w-full rounded-xl border border-[#E2E8F0] bg-white text-sm pl-3.5 pr-9 py-2 text-left relative flex items-center justify-between focus:outline-none focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB] transition">
                                <span class="truncate"
                                      :class="category_id ? 'text-slate-900 font-medium' : 'text-slate-600'"
                                      x-text="findCategory(category_id) ? findCategory(category_id).name : 'All Categories'"></span>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </button>

                            <div x-show="open" x-cloak @click.outside="open = false"
                                 x-transition
                                 class="absolute z-[60] mt-1.5 w-full bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                                <div class="p-2 border-b border-slate-100 bg-slate-50/70">
                                    <input type="text" x-ref="reportCatInput" x-model="q"
                                           placeholder="Search category..."
                                           class="w-full rounded-lg border-slate-200 text-xs px-3 py-1.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                </div>
                                <div class="max-h-56 overflow-y-auto divide-y divide-slate-50 text-xs">
                                    <button type="button"
                                            x-show="!q || 'all categories'.includes(q.toLowerCase().trim())"
                                            @click="category_id = ''; open = false; q = ''; $nextTick(() => submitFilters())"
                                            class="w-full px-3.5 py-2.5 text-left hover:bg-blue-50/60 flex items-center justify-between transition"
                                            :class="category_id === '' ? 'bg-blue-50/80 font-semibold text-[#2563EB]' : 'text-slate-700'">
                                        <span class="truncate">All Categories</span>
                                    </button>
                                    <template x-for="cat in filterCategories(q)" :key="cat.id">
                                        <button type="button"
                                                @click="category_id = String(cat.id); open = false; q = ''; $nextTick(() => submitFilters())"
                                                class="w-full px-3.5 py-2.5 text-left hover:bg-blue-50/60 flex items-center justify-between transition"
                                                :class="String(category_id) === String(cat.id) ? 'bg-blue-50/80 font-semibold text-[#2563EB]' : 'text-slate-700'">
                                            <span class="truncate" x-text="cat.name"></span>
                                        </button>
                                    </template>
                                    <div x-show="filterCategories(q).length === 0 && !('all categories'.includes(q.toLowerCase().trim()))"
                                         class="px-3.5 py-4 text-center text-slate-400">
                                        No matching categories found.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reset Filter Button -->
                    <div class="sm:col-span-2 flex items-center">
                        <button type="button"
                                @click="resetFilters()"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                            Reset Filters
                        </button>
                    </div>
                </div>

                <!-- Export CSV & Print Report Buttons -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <a :href="exportCsvUrl"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-[#E2E8F0] bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition">
                        <svg class="w-4 h-4 text-[#059669]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export CSV</span>
                    </a>

                    <button type="button"
                            @click="window.print()"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Print Report</span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Role-Aware Tab Navigation (Hidden when printing) -->
        <div class="no-print border-b border-[#E2E8F0]">
            <nav class="flex flex-wrap gap-2 -mb-px">
                @if($canViewSales)
                    <button type="button"
                            @click="switchTab('sales')"
                            :class="activeTab === 'sales'
                                ? 'border-[#2563EB] text-[#2563EB] bg-blue-50/60'
                                : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300'"
                            class="inline-flex items-center gap-2 px-4 py-3 border-b-2 text-xs font-bold rounded-t-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                        </svg>
                        <span>Sales Trend & Sales Report</span>
                    </button>
                @endif

                @if($canViewStock)
                    <button type="button"
                            @click="switchTab('stock')"
                            :class="activeTab === 'stock'
                                ? 'border-[#2563EB] text-[#2563EB] bg-blue-50/60'
                                : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300'"
                            class="inline-flex items-center gap-2 px-4 py-3 border-b-2 text-xs font-bold rounded-t-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>Current Stock & Valuation Report</span>
                    </button>
                @endif

                @if($canViewPurchases)
                    <button type="button"
                            @click="switchTab('purchases')"
                            :class="activeTab === 'purchases'
                                ? 'border-[#2563EB] text-[#2563EB] bg-blue-50/60'
                                : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300'"
                            class="inline-flex items-center gap-2 px-4 py-3 border-b-2 text-xs font-bold rounded-t-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Purchase Order & Procurement Report</span>
                    </button>
                @endif
            </nav>
        </div>

        <!-- ========================================================= -->
        <!-- TAB 1: SALES TREND & SALES REPORT (Admin & Cashier)       -->
        <!-- ========================================================= -->
        @if($canViewSales)
            <div x-show="activeTab === 'sales'" x-cloak class="space-y-6">
                <!-- 4 Summary KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                    <div class="bg-white border border-[#E2E8F0] rounded-2xl p-5 shadow-xs">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Transactions</span>
                        <div class="mt-2 text-2xl font-bold text-slate-900">
                            {{ number_format($salesSummary['total_transactions'], 0, ',', '.') }}
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Completed retail sales lines</p>
                    </div>

                    <div class="bg-white border border-[#E2E8F0] rounded-2xl p-5 shadow-xs">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Units Sold</span>
                        <div class="mt-2 text-2xl font-bold text-[#2563EB]">
                            {{ number_format($salesSummary['total_units_sold'], 0, ',', '.') }} Units
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Outgoing electronics volume</p>
                    </div>

                    <div class="bg-white border border-[#E2E8F0] rounded-2xl p-5 shadow-xs">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Sales Revenue</span>
                        <div class="mt-2 text-2xl font-bold text-slate-900">
                            Rp {{ number_format($salesSummary['total_revenue'], 0, ',', '.') }}
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Gross retail sales value</p>
                    </div>

                    <div class="bg-white border border-[#E2E8F0] rounded-2xl p-5 shadow-xs">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Estimated Gross Profit</span>
                        <div class="mt-2 text-2xl font-bold text-[#059669]">
                            Rp {{ number_format($salesSummary['estimated_gross_profit'], 0, ',', '.') }}
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Net margin over purchase cost</p>
                    </div>
                </div>

                <!-- 2 Side-by-Side Interactive Chart.js Visualizations -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Chart 1: Daily Sales Revenue & Units Trend -->
                    <div class="lg:col-span-7 bg-white border border-[#E2E8F0] rounded-2xl p-6 shadow-xs flex flex-col">
                        <div class="pb-4 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">Daily Sales Revenue & Units Trend</h2>
                                <p class="text-xs text-slate-500">Daily revenue (Rp) vs. quantity sold in selected period</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-50 text-[#2563EB]">
                                Combo Chart
                            </span>
                        </div>

                        @if($dailySalesChart['has_data'])
                            <div class="mt-4 h-72 relative flex-1">
                                <canvas x-ref="dailySalesCanvas"></canvas>
                            </div>
                        @else
                            <div class="h-72 flex flex-col items-center justify-center text-center p-6">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-700">No Sales Recorded in Selected Period</p>
                                <p class="text-xs text-slate-500 mt-1">Adjust the start/end date or category filter to view sales trend analytics.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Chart 2: Sales by Category & Top Selling Products -->
                    <div class="lg:col-span-5 bg-white border border-[#E2E8F0] rounded-2xl p-6 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="pb-4 border-b border-slate-100 flex items-center justify-between">
                                <div>
                                    <h2 class="text-sm font-bold text-slate-900">Sales by Category & Top Products</h2>
                                    <p class="text-xs text-slate-500">Revenue share across electronics categories</p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-[#059669]">
                                    Distribution
                                </span>
                            </div>

                            @if($categorySalesChart['has_data'])
                                <div class="mt-4 h-52 relative">
                                    <canvas x-ref="categorySalesCanvas"></canvas>
                                </div>

                                <!-- Top Selling Products Mini List -->
                                <div class="mt-4 pt-4 border-t border-slate-100 space-y-2">
                                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Top Selling Products</h3>
                                    @foreach($topSellingProducts->take(3) as $topProd)
                                        <div class="flex items-center justify-between text-xs">
                                            <div class="min-w-0 pr-2">
                                                <span class="font-semibold text-slate-800 truncate block">{{ $topProd['name'] }}</span>
                                                <span class="text-[11px] text-slate-400 font-mono">{{ $topProd['sku'] }} &bull; {{ $topProd['units_sold'] }} Sold</span>
                                            </div>
                                            <span class="font-bold text-[#2563EB] shrink-0">
                                                Rp {{ number_format($topProd['revenue'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="h-72 flex flex-col items-center justify-center text-center p-6">
                                    <p class="text-sm font-semibold text-slate-700">No Category Breakdown Available</p>
                                    <p class="text-xs text-slate-500 mt-1">Sales distribution will appear once transactions are recorded in this range.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Detailed Sales Transactions Table -->
                <div class="bg-white border border-[#E2E8F0] rounded-2xl shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-[#E2E8F0] flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Detailed Sales Report</h2>
                            <p class="text-xs text-slate-500">Itemized outgoing retail sales transactions within the selected period</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider border-b border-[#E2E8F0]">
                                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Reference Code</th>
                                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Date</th>
                                    <th class="py-3.5 px-4 font-semibold min-w-[240px]">Product & SKU</th>
                                    <th class="py-3.5 px-4 font-semibold text-center whitespace-nowrap">Qty</th>
                                    <th class="py-3.5 px-4 font-semibold text-right whitespace-nowrap">Unit Price</th>
                                    <th class="py-3.5 px-4 font-semibold text-right whitespace-nowrap">Total Revenue</th>
                                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Cashier</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($salesReport as $sale)
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3.5 px-4 font-mono text-xs font-bold text-slate-900 whitespace-nowrap">
                                            {{ $sale->reference_code }}
                                        </td>
                                        <td class="py-3.5 px-4 text-xs text-slate-600 whitespace-nowrap">
                                            {{ $sale->transaction_date?->format('d M Y, H:i') }}
                                        </td>
                                        <td class="py-3.5 px-4 min-w-[240px]">
                                            <div class="font-semibold text-slate-900">{{ $sale->product->name ?? '-' }}</div>
                                            <div class="text-xs text-slate-500">{{ $sale->product->sku ?? '-' }} &bull; {{ $sale->product->category->name ?? '-' }}</div>
                                        </td>
                                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-[#2563EB]">
                                                {{ $sale->quantity }} {{ $sale->product->unit ?? 'Unit' }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-right text-xs text-slate-600 whitespace-nowrap">
                                            Rp {{ number_format((float) $sale->unit_price, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3.5 px-4 text-right font-semibold text-slate-900 whitespace-nowrap">
                                            Rp {{ number_format((float) $sale->total_price, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3.5 px-4 text-xs text-slate-700 whitespace-nowrap">
                                            {{ $sale->user->name ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-10 px-4 text-center text-sm text-slate-500">
                                            No sales transactions found for the selected filter period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($salesReport && $salesReport->hasPages())
                        <div class="no-print px-6 py-4 border-t border-[#E2E8F0] bg-slate-50/50">
                            {{ $salesReport->appends(['tab' => 'sales'])->links('vendor.pagination.tailwind') }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- ========================================================= -->
        <!-- TAB 2: CURRENT STOCK & INVENTORY VALUATION (Admin/Gudang) -->
        <!-- ========================================================= -->
        @if($canViewStock)
            <div x-show="activeTab === 'stock'" x-cloak class="space-y-6">
                <!-- 4 Summary KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                    <div class="bg-white border border-[#E2E8F0] rounded-2xl p-5 shadow-xs">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Active SKUs</span>
                        <div class="mt-2 text-2xl font-bold text-slate-900">
                            {{ number_format($stockSummary['total_skus'], 0, ',', '.') }} SKUs
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            Low stock alerts: <span class="font-bold text-[#E11D48]">{{ $stockSummary['low_stock_count'] }} items</span>
                        </p>
                    </div>

                    <div class="bg-white border border-[#E2E8F0] rounded-2xl p-5 shadow-xs">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Physical Units</span>
                        <div class="mt-2 text-2xl font-bold text-[#2563EB]">
                            {{ number_format($stockSummary['total_physical_units'], 0, ',', '.') }} Units
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Total on-hand warehouse stock</p>
                    </div>

                    <div class="bg-white border border-[#E2E8F0] rounded-2xl p-5 shadow-xs">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Inventory Asset Value</span>
                        <div class="mt-2 text-2xl font-bold text-slate-900">
                            Rp {{ number_format($stockSummary['total_asset_value'], 0, ',', '.') }}
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Valued at supplier purchase cost</p>
                    </div>

                    <div class="bg-white border border-[#E2E8F0] rounded-2xl p-5 shadow-xs">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Potential Retail Value</span>
                        <div class="mt-2 text-2xl font-bold text-[#059669]">
                            Rp {{ number_format($stockSummary['potential_retail_value'], 0, ',', '.') }}
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Valued at retail selling price</p>
                    </div>
                </div>

                <!-- Current Stock & Valuation Table -->
                <div class="bg-white border border-[#E2E8F0] rounded-2xl shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-[#E2E8F0] flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Current Stock & Inventory Valuation Report</h2>
                            <p class="text-xs text-slate-500">Real-time warehouse stock levels and asset valuation per SKU</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider border-b border-[#E2E8F0]">
                                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">SKU</th>
                                    <th class="py-3.5 px-4 font-semibold min-w-[240px]">Product Name</th>
                                    <th class="py-3.5 px-4 font-semibold">Category</th>
                                    <th class="py-3.5 px-4 font-semibold text-center whitespace-nowrap">Current Stock</th>
                                    <th class="py-3.5 px-4 font-semibold text-center whitespace-nowrap">Min Stock</th>
                                    <th class="py-3.5 px-4 font-semibold text-center whitespace-nowrap">Status Badge</th>
                                    <th class="py-3.5 px-4 font-semibold text-right whitespace-nowrap">Purchase Price</th>
                                    <th class="py-3.5 px-4 font-semibold text-right whitespace-nowrap">Asset Valuation</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($stockReport as $product)
                                    @php
                                        $assetValuation = (int) $product->stock * (float) $product->purchase_price;
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3.5 px-4 font-mono text-xs font-semibold text-slate-700 whitespace-nowrap">
                                            {{ $product->sku }}
                                        </td>
                                        <td class="py-3.5 px-4 min-w-[240px]">
                                            <div class="font-semibold text-slate-900">{{ $product->name }}</div>
                                            <div class="text-xs text-slate-500">Brand: {{ $product->brand }}</div>
                                        </td>
                                        <td class="py-3.5 px-4 text-xs text-slate-600">
                                            {{ $product->category->name ?? '-' }}
                                        </td>
                                        <td class="py-3.5 px-4 text-center font-bold text-slate-900 whitespace-nowrap">
                                            {{ $product->stock }} {{ $product->unit }}
                                        </td>
                                        <td class="py-3.5 px-4 text-center text-xs text-slate-500 whitespace-nowrap">
                                            {{ $product->min_stock }} {{ $product->unit }}
                                        </td>
                                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                            @if($product->isLowStock())
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-100 text-[#E11D48]">
                                                    Low Stock
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-[#059669]">
                                                    In Stock
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4 text-right text-xs text-slate-600 whitespace-nowrap">
                                            Rp {{ number_format((float) $product->purchase_price, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3.5 px-4 text-right font-semibold text-slate-900 whitespace-nowrap">
                                            Rp {{ number_format($assetValuation, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-10 px-4 text-center text-sm text-slate-500">
                                            No products matched your selected category filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($stockReport && $stockReport->hasPages())
                        <div class="no-print px-6 py-4 border-t border-[#E2E8F0] bg-slate-50/50">
                            {{ $stockReport->appends(['tab' => 'stock'])->links('vendor.pagination.tailwind') }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- ========================================================= -->
        <!-- TAB 3: PURCHASE ORDER & PROCUREMENT REPORT (Admin/Gudang) -->
        <!-- ========================================================= -->
        @if($canViewPurchases)
            <div x-show="activeTab === 'purchases'" x-cloak class="space-y-6">
                <!-- 4 Summary KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                    <div class="bg-white border border-[#E2E8F0] rounded-2xl p-5 shadow-xs">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Purchase Orders</span>
                        <div class="mt-2 text-2xl font-bold text-slate-900">
                            {{ number_format($purchaseSummary['total_pos'], 0, ',', '.') }} POs
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Issued in selected date range</p>
                    </div>

                    <div class="bg-white border border-[#E2E8F0] rounded-2xl p-5 shadow-xs">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Received POs</span>
                        <div class="mt-2 text-2xl font-bold text-[#059669]">
                            {{ number_format($purchaseSummary['received_pos'], 0, ',', '.') }} Completed
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Goods received into warehouse</p>
                    </div>

                    <div class="bg-white border border-[#E2E8F0] rounded-2xl p-5 shadow-xs">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pending / Processing POs</span>
                        <div class="mt-2 text-2xl font-bold text-[#D97706]">
                            {{ number_format($purchaseSummary['pending_processing_pos'], 0, ',', '.') }} Open
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Awaiting supplier delivery</p>
                    </div>

                    <div class="bg-white border border-[#E2E8F0] rounded-2xl p-5 shadow-xs">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Procurement Spend</span>
                        <div class="mt-2 text-2xl font-bold text-[#2563EB]">
                            Rp {{ number_format($purchaseSummary['total_procurement_spend'], 0, ',', '.') }}
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Active & received PO commitment</p>
                    </div>
                </div>

                <!-- Purchase Orders Report Table -->
                <div class="bg-white border border-[#E2E8F0] rounded-2xl shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-[#E2E8F0] flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Purchase Order & Procurement Report</h2>
                            <p class="text-xs text-slate-500">Supplier restocking orders and procurement expenditure breakdown</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider border-b border-[#E2E8F0]">
                                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">PO Number</th>
                                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Order Date</th>
                                    <th class="py-3.5 px-4 font-semibold min-w-[200px]">Supplier</th>
                                    <th class="py-3.5 px-4 font-semibold text-center whitespace-nowrap">Status Badge</th>
                                    <th class="py-3.5 px-4 font-semibold text-center whitespace-nowrap">Total Items</th>
                                    <th class="py-3.5 px-4 font-semibold text-right whitespace-nowrap">Total Amount</th>
                                    <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Created By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($purchaseReport as $po)
                                    @php
                                        $normalizedStatus = match($po->status) {
                                            'diproses', 'processing' => 'processing',
                                            'diterima', 'received' => 'received',
                                            'dibatalkan', 'cancelled' => 'cancelled',
                                            default => 'pending',
                                        };

                                        $badge = match($normalizedStatus) {
                                            'processing' => ['label' => 'Processing', 'style' => 'bg-blue-100 text-[#2563EB] border-blue-200'],
                                            'received' => ['label' => 'Received', 'style' => 'bg-emerald-100 text-[#059669] border-emerald-200'],
                                            'cancelled' => ['label' => 'Cancelled', 'style' => 'bg-rose-100 text-[#E11D48] border-rose-200'],
                                            default => ['label' => 'Pending', 'style' => 'bg-amber-100 text-[#D97706] border-amber-200'],
                                        };
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3.5 px-4 font-mono text-xs font-bold text-slate-900 whitespace-nowrap">
                                            <a href="{{ route('purchase-orders.show', $po) }}" class="hover:text-[#2563EB] hover:underline">
                                                {{ $po->po_number }}
                                            </a>
                                        </td>
                                        <td class="py-3.5 px-4 text-xs text-slate-600 whitespace-nowrap">
                                            {{ $po->order_date?->format('d M Y') }}
                                        </td>
                                        <td class="py-3.5 px-4 min-w-[200px]">
                                            <div class="font-semibold text-slate-900">{{ $po->supplier->name ?? '-' }}</div>
                                            <div class="text-xs text-slate-500">{{ $po->supplier->code ?? '' }}</div>
                                        </td>
                                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badge['style'] }}">
                                                {{ $badge['label'] }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                                {{ $po->items->count() }} Items ({{ $po->items->sum('quantity') }} Qty)
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-right font-semibold text-slate-900 whitespace-nowrap">
                                            Rp {{ number_format((float) $po->total_amount, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3.5 px-4 text-xs text-slate-700 whitespace-nowrap">
                                            {{ $po->user->name ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-10 px-4 text-center text-sm text-slate-500">
                                            No Purchase Orders found for the selected filter criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($purchaseReport && $purchaseReport->hasPages())
                        <div class="no-print px-6 py-4 border-t border-[#E2E8F0] bg-slate-50/50">
                            {{ $purchaseReport->appends(['tab' => 'purchases'])->links('vendor.pagination.tailwind') }}
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
