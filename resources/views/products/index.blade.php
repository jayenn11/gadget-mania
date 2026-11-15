<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <div>
                <h1 class="text-lg font-bold text-slate-900">Products</h1>
                <p class="text-xs text-slate-500">Manage electronics catalog, purchase/retail pricing, and real-time stock thresholds</p>
            </div>
        </div>
    </x-slot>

    @php
        $initialModalOpen = $errors->any();
        $initialModalMode = old('_modal_mode', 'create');
        $initialForm = [
            'id' => old('_edit_id', ''),
            'sku' => old('sku', ''),
            'brand' => old('brand', ''),
            'name' => old('name', ''),
            'category_id' => (string) old('category_id', ''),
            'supplier_id' => (string) old('supplier_id', ''),
            'purchase_price' => old('purchase_price', ''),
            'selling_price' => old('selling_price', ''),
            'stock' => old('stock', 0),
            'min_stock' => old('min_stock', 5),
            'unit' => old('unit', 'Unit'),
        ];

        $categoriesCatalog = $categories->map(fn ($c) => [
            'id' => (string) $c->id,
            'name' => $c->name,
        ])->values();

        $suppliersCatalog = $suppliers->map(fn ($s) => [
            'id' => (string) $s->id,
            'code' => $s->code,
            'name' => $s->name,
        ])->values();

        $stockStatusOptions = [
            ['value' => '', 'label' => 'All Stock Status'],
            ['value' => 'low', 'label' => 'Low Stock'],
            ['value' => 'safe', 'label' => 'In Stock (Safe)'],
        ];

        $unitOptions = [
            ['value' => 'Unit', 'label' => 'Unit'],
            ['value' => 'Pcs', 'label' => 'Pcs'],
            ['value' => 'Box', 'label' => 'Box'],
            ['value' => 'Set', 'label' => 'Set'],
            ['value' => 'Pack', 'label' => 'Pack'],
        ];
    @endphp

    <div x-data="{
            search: @js(request('search', '')),
            category_id: @js((string) request('category_id', '')),
            stock_status: @js((string) request('stock_status', '')),
            isLoading: false,
            modalOpen: @js($initialModalOpen),
            modalMode: @js($initialModalMode),
            baseUrl: @js(route('products.index')),
            categoriesCatalog: @js($categoriesCatalog),
            suppliersCatalog: @js($suppliersCatalog),
            stockStatusOptions: @js($stockStatusOptions),
            unitOptions: @js($unitOptions),
            form: @js($initialForm),
            openCreateModal() {
                this.modalMode = 'create';
                this.form = {
                    id: '',
                    sku: '',
                    brand: '',
                    name: '',
                    category_id: '',
                    supplier_id: '',
                    purchase_price: '',
                    selling_price: '',
                    stock: 0,
                    min_stock: 5,
                    unit: 'Unit'
                };
                this.modalOpen = true;
            },
            openEditModal(product) {
                this.modalMode = 'edit';
                this.form = {
                    id: product.id,
                    sku: product.sku,
                    brand: product.brand,
                    name: product.name,
                    category_id: String(product.category_id || ''),
                    supplier_id: String(product.supplier_id || ''),
                    purchase_price: parseFloat(product.purchase_price),
                    selling_price: parseFloat(product.selling_price),
                    stock: product.stock,
                    min_stock: product.min_stock,
                    unit: product.unit || 'Unit'
                };
                this.modalOpen = true;
            },
            findCategory(categoryId) {
                return this.categoriesCatalog.find(c => c.id === String(categoryId || '')) || null;
            },
            filterCategories(q = '') {
                const query = (q || '').toLowerCase().trim();
                if (!query) return this.categoriesCatalog;
                return this.categoriesCatalog.filter(c => c.name.toLowerCase().includes(query));
            },
            findSupplier(supplierId) {
                return this.suppliersCatalog.find(s => s.id === String(supplierId || '')) || null;
            },
            filterSuppliers(q = '') {
                const query = (q || '').toLowerCase().trim();
                if (!query) return this.suppliersCatalog;
                return this.suppliersCatalog.filter(s =>
                    s.name.toLowerCase().includes(query) || s.code.toLowerCase().includes(query)
                );
            },
            findStockStatusLabel(val) {
                const found = this.stockStatusOptions.find(o => o.value === String(val || ''));
                return found ? found.label : 'All Stock Status';
            },
            filterStockStatuses(q = '') {
                const query = (q || '').toLowerCase().trim();
                if (!query) return this.stockStatusOptions;
                return this.stockStatusOptions.filter(o => o.label.toLowerCase().includes(query));
            },
            filterUnits(q = '') {
                const query = (q || '').toLowerCase().trim();
                if (!query) return this.unitOptions;
                return this.unitOptions.filter(u => u.label.toLowerCase().includes(query));
            },
            get formAction() {
                return this.modalMode === 'edit' && this.form.id
                    ? `${this.baseUrl}/${this.form.id}`
                    : this.baseUrl;
            },
            async applyFilter(pageUrl = null) {
                this.isLoading = true;
                try {
                    const url = new URL(pageUrl || this.baseUrl, window.location.origin);
                    if (!pageUrl) {
                        if (this.search) url.searchParams.set('search', this.search);
                        else url.searchParams.delete('search');

                        if (this.category_id) url.searchParams.set('category_id', this.category_id);
                        else url.searchParams.delete('category_id');

                        if (this.stock_status) url.searchParams.set('stock_status', this.stock_status);
                        else url.searchParams.delete('stock_status');
                    }

                    const response = await fetch(url.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newContainer = doc.getElementById('table-container');
                    if (newContainer) {
                        document.getElementById('table-container').innerHTML = newContainer.innerHTML;
                        window.history.replaceState({}, '', url.toString());
                    }
                } finally {
                    this.isLoading = false;
                }
            },
            resetFilters() {
                this.search = '';
                this.category_id = '';
                this.stock_status = '';
                this.applyFilter();
            }
        }"
        @keydown.escape.window="modalOpen = false"
        class="space-y-6">

        <!-- Action & Live Filter Bar -->
        <div class="bg-white border border-[#E2E8F0] rounded-2xl p-4 sm:p-5 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                    <!-- Live Search Input -->
                    <div class="sm:col-span-5 relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text"
                               x-model="search"
                               x-on:input.debounce.350ms="applyFilter()"
                               placeholder="Search product name, brand, or SKU..."
                               class="w-full rounded-xl border-[#E2E8F0] text-sm pl-9 pr-3.5 py-2 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <!-- Category Custom Searchable Auto-Filter -->
                    <div class="sm:col-span-4" x-data="{ open: false, q: '' }">
                        <input type="hidden" name="filter_category_id" :value="category_id">
                        <div class="relative">
                            <button type="button"
                                    @click="open = !open; if (open) $nextTick(() => $refs.filterCatInput.focus())"
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
                                    <input type="text" x-ref="filterCatInput" x-model="q"
                                           placeholder="Search category..."
                                           class="w-full rounded-lg border-slate-200 text-xs px-3 py-1.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                </div>
                                <div class="max-h-56 overflow-y-auto divide-y divide-slate-50 text-xs">
                                    <button type="button"
                                            x-show="!q || 'all categories'.includes(q.toLowerCase().trim())"
                                            @click="category_id = ''; open = false; q = ''; applyFilter()"
                                            class="w-full px-3.5 py-2.5 text-left hover:bg-blue-50/60 flex items-center justify-between transition"
                                            :class="category_id === '' ? 'bg-blue-50/80 font-semibold text-[#2563EB]' : 'text-slate-700'">
                                        <span class="truncate">All Categories</span>
                                    </button>
                                    <template x-for="cat in filterCategories(q)" :key="cat.id">
                                        <button type="button"
                                                @click="category_id = String(cat.id); open = false; q = ''; applyFilter()"
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

                    <!-- Stock Status Custom Searchable Auto-Filter -->
                    <div class="sm:col-span-3 flex items-center gap-2">
                        <div class="flex-1" x-data="{ open: false, q: '' }">
                            <input type="hidden" name="filter_stock_status" :value="stock_status">
                            <div class="relative">
                                <button type="button"
                                        @click="open = !open; if (open) $nextTick(() => $refs.filterStockInput.focus())"
                                        class="w-full rounded-xl border border-[#E2E8F0] bg-white text-sm pl-3.5 pr-9 py-2 text-left relative flex items-center justify-between focus:outline-none focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB] transition">
                                    <span class="truncate"
                                          :class="stock_status ? 'text-slate-900 font-medium' : 'text-slate-600'"
                                          x-text="findStockStatusLabel(stock_status)"></span>
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
                                        <input type="text" x-ref="filterStockInput" x-model="q"
                                               placeholder="Search stock status..."
                                               class="w-full rounded-lg border-slate-200 text-xs px-3 py-1.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                    </div>
                                    <div class="max-h-52 overflow-y-auto divide-y divide-slate-50 text-xs">
                                        <template x-for="opt in filterStockStatuses(q)" :key="opt.value">
                                            <button type="button"
                                                    @click="stock_status = opt.value; open = false; q = ''; applyFilter()"
                                                    class="w-full px-3.5 py-2.5 text-left hover:bg-blue-50/60 flex items-center justify-between transition"
                                                    :class="stock_status === opt.value ? 'bg-blue-50/80 font-semibold text-[#2563EB]' : 'text-slate-700'">
                                                <span class="truncate" x-text="opt.label"></span>
                                            </button>
                                        </template>
                                        <div x-show="filterStockStatuses(q).length === 0" class="px-3.5 py-4 text-center text-slate-400">
                                            No matching status found.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button"
                                x-show="search !== '' || category_id !== '' || stock_status !== ''"
                                x-cloak
                                @click="resetFilters()"
                                class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 shrink-0 transition">
                            Reset
                        </button>
                    </div>
                </div>

                @if(auth()->user()->isAdmin() || auth()->user()->isGudang())
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('categories.index') }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-[#E2E8F0] bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                            Categories
                        </a>
                        <button type="button"
                                @click="openCreateModal()"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Add Product</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Products Table Container (Updated dynamically via fetch) -->
        <div id="table-container"
             @click="
                const link = $event.target.closest('nav[role=navigation] a');
                if (link) {
                    $event.preventDefault();
                    applyFilter(link.href);
                }
             "
             :class="{ 'opacity-60 pointer-events-none': isLoading }"
             class="bg-white border border-[#E2E8F0] rounded-2xl shadow-xs overflow-hidden transition-opacity duration-150">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider border-b border-[#E2E8F0]">
                            <th class="py-3.5 px-4 font-semibold">SKU</th>
                            <th class="py-3.5 px-4 font-semibold">Product Name & Brand</th>
                            <th class="py-3.5 px-4 font-semibold">Category</th>
                            <th class="py-3.5 px-4 font-semibold">Supplier</th>
                            @if(auth()->user()->isAdmin() || auth()->user()->isGudang())
                                <th class="py-3.5 px-4 font-semibold text-right">Purchase Price</th>
                            @endif
                            <th class="py-3.5 px-4 font-semibold text-right">Selling Price</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Stock & Status</th>
                            @if(auth()->user()->isAdmin() || auth()->user()->isGudang())
                                <th class="py-3.5 px-4 font-semibold text-right">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($products as $product)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 font-mono text-xs font-semibold text-slate-700 whitespace-nowrap">
                                    {{ $product->sku }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-900">{{ $product->name }}</div>
                                    <div class="text-xs text-slate-500">Brand: {{ $product->brand }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ $product->category->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600">
                                    {{ $product->supplier->name ?? 'Unassigned' }}
                                </td>
                                @if(auth()->user()->isAdmin() || auth()->user()->isGudang())
                                    <td class="py-3.5 px-4 text-right text-xs text-slate-600 whitespace-nowrap">
                                        Rp {{ number_format((float) $product->purchase_price, 0, ',', '.') }}
                                    </td>
                                @endif
                                <td class="py-3.5 px-4 text-right font-semibold text-slate-900 whitespace-nowrap">
                                    Rp {{ number_format((float) $product->selling_price, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-xs font-bold text-slate-900">
                                            {{ $product->stock }} {{ $product->unit }}
                                            <span class="text-[11px] font-normal text-slate-400">(Min: {{ $product->min_stock }})</span>
                                        </span>
                                        @if($product->isLowStock())
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-100 text-[#E11D48]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#E11D48]"></span>
                                                Low Stock
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-[#059669]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#059669]"></span>
                                                In Stock
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                @if(auth()->user()->isAdmin() || auth()->user()->isGudang())
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button type="button"
                                                    @click="openEditModal(@js($product->only(['id', 'sku', 'brand', 'name', 'category_id', 'supplier_id', 'purchase_price', 'selling_price', 'stock', 'min_stock', 'unit'])))"
                                                    class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-xs font-semibold text-slate-700 transition">
                                                Edit
                                            </button>
                                            <button type="button"
                                                    @click="$dispatch('open-delete-modal', {
                                                        action: '{{ route('products.destroy', $product) }}',
                                                        title: 'Delete Product',
                                                        itemName: @js($product->name)
                                                    })"
                                                    class="px-2.5 py-1.5 rounded-lg border border-rose-200 hover:bg-rose-50 text-xs font-semibold text-[#E11D48] transition">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 px-4 text-center text-sm text-slate-500">
                                    No products matched your filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="px-6 py-4 border-t border-[#E2E8F0] bg-slate-50/50">
                    {{ $products->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>

        <!-- Create / Edit Product Modal -->
        @if(auth()->user()->isAdmin() || auth()->user()->isGudang())
            <div x-show="modalOpen" x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div x-show="modalOpen"
                         x-transition.opacity
                         @click="modalOpen = false"
                         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>

                    <!-- Modal Dialog (overflow-visible so dropdowns float freely) -->
                    <div x-show="modalOpen"
                         x-transition
                         class="relative w-full max-w-2xl bg-white border border-[#E2E8F0] rounded-2xl shadow-xl p-6 sm:p-8 z-10 my-8 overflow-visible">
                        <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900" x-text="modalMode === 'edit' ? 'Edit Product' : 'Add New Product'"></h2>
                                <p class="text-xs text-slate-500 mt-0.5" x-text="modalMode === 'edit' ? 'Update product specifications, pricing, and stock thresholds.' : 'Enter product details to register a new item in the catalog.'"></p>
                            </div>
                            <button type="button" @click="modalOpen = false"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form method="POST" :action="formAction" class="space-y-4 mt-5">
                            @csrf
                            <template x-if="modalMode === 'edit'">
                                <input type="hidden" name="_method" value="PUT">
                            </template>
                            <input type="hidden" name="_modal_mode" x-model="modalMode">
                            <input type="hidden" name="_edit_id" x-model="form.id">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- SKU -->
                                <div>
                                    <label for="modal_sku" class="block text-sm font-semibold text-slate-700 mb-1">
                                        SKU Code <span class="text-[#E11D48]">*</span>
                                    </label>
                                    <input type="text" id="modal_sku" name="sku" x-model="form.sku" required
                                           placeholder="e.g. GM-IP16-128"
                                           class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                    <x-input-error :messages="$errors->get('sku')" class="mt-1" />
                                </div>

                                <!-- Brand -->
                                <div>
                                    <label for="modal_brand" class="block text-sm font-semibold text-slate-700 mb-1">
                                        Brand <span class="text-[#E11D48]">*</span>
                                    </label>
                                    <input type="text" id="modal_brand" name="brand" x-model="form.brand" required
                                           placeholder="e.g. Apple, Samsung, ASUS"
                                           class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                    <x-input-error :messages="$errors->get('brand')" class="mt-1" />
                                </div>
                            </div>

                            <!-- Product Name -->
                            <div>
                                <label for="modal_name" class="block text-sm font-semibold text-slate-700 mb-1">
                                    Product Full Name <span class="text-[#E11D48]">*</span>
                                </label>
                                <input type="text" id="modal_name" name="name" x-model="form.name" required
                                       placeholder="e.g. iPhone 15 Pro 256GB Natural Titanium"
                                       class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                <x-input-error :messages="$errors->get('name')" class="mt-1" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Category Custom Searchable Dropdown -->
                                <div x-data="{ open: false, q: '' }">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                                        Category <span class="text-[#E11D48]">*</span>
                                    </label>
                                    <input type="hidden" name="category_id" :value="form.category_id" required>

                                    <div class="relative">
                                        <button type="button"
                                                @click="open = !open; if (open) $nextTick(() => $refs.modalCatInput.focus())"
                                                class="w-full rounded-xl border border-[#E2E8F0] bg-white text-sm pl-3.5 pr-9 py-2.5 text-left relative flex items-center justify-between focus:outline-none focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB] transition">
                                            <span class="truncate"
                                                  :class="form.category_id ? 'text-slate-900 font-medium' : 'text-slate-400'"
                                                  x-text="findCategory(form.category_id) ? findCategory(form.category_id).name : '-- Select Category --'"></span>
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
                                                <input type="text" x-ref="modalCatInput" x-model="q"
                                                       placeholder="Search category..."
                                                       class="w-full rounded-lg border-slate-200 text-xs px-3 py-1.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                            </div>
                                            <div class="max-h-52 overflow-y-auto divide-y divide-slate-50 text-xs">
                                                <template x-for="cat in filterCategories(q)" :key="cat.id">
                                                    <button type="button"
                                                            @click="form.category_id = String(cat.id); open = false; q = ''"
                                                            class="w-full px-3.5 py-2.5 text-left hover:bg-blue-50/60 flex items-center justify-between transition"
                                                            :class="String(form.category_id) === String(cat.id) ? 'bg-blue-50/80 font-semibold text-[#2563EB]' : 'text-slate-700'">
                                                        <span class="truncate" x-text="cat.name"></span>
                                                    </button>
                                                </template>
                                                <div x-show="filterCategories(q).length === 0" class="px-3.5 py-4 text-center text-slate-400">
                                                    No matching categories found.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                                </div>

                                <!-- Primary Supplier Custom Searchable Dropdown -->
                                <div x-data="{ open: false, q: '' }">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                                        Primary Supplier <span class="text-xs font-normal text-slate-400">(Optional)</span>
                                    </label>
                                    <input type="hidden" name="supplier_id" :value="form.supplier_id">

                                    <div class="relative">
                                        <button type="button"
                                                @click="open = !open; if (open) $nextTick(() => $refs.modalSupInput.focus())"
                                                class="w-full rounded-xl border border-[#E2E8F0] bg-white text-sm pl-3.5 pr-9 py-2.5 text-left relative flex items-center justify-between focus:outline-none focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB] transition">
                                            <span class="truncate"
                                                  :class="form.supplier_id ? 'text-slate-900 font-medium' : 'text-slate-400'"
                                                  x-text="findSupplier(form.supplier_id) ? `${findSupplier(form.supplier_id).name} (${findSupplier(form.supplier_id).code})` : '-- Select Supplier --'"></span>
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
                                                <input type="text" x-ref="modalSupInput" x-model="q"
                                                       placeholder="Search supplier name or code..."
                                                       class="w-full rounded-lg border-slate-200 text-xs px-3 py-1.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                            </div>
                                            <div class="max-h-52 overflow-y-auto divide-y divide-slate-50 text-xs">
                                                <button type="button"
                                                        x-show="!q"
                                                        @click="form.supplier_id = ''; open = false; q = ''"
                                                        class="w-full px-3.5 py-2.5 text-left hover:bg-slate-50 flex items-center justify-between text-slate-500 transition"
                                                        :class="form.supplier_id === '' ? 'bg-blue-50/80 font-semibold text-[#2563EB]' : ''">
                                                    <span class="truncate">-- Unassigned / No Default Supplier --</span>
                                                </button>
                                                <template x-for="sup in filterSuppliers(q)" :key="sup.id">
                                                    <button type="button"
                                                            @click="form.supplier_id = String(sup.id); open = false; q = ''"
                                                            class="w-full px-3.5 py-2.5 text-left hover:bg-blue-50/60 flex items-center justify-between transition"
                                                            :class="String(form.supplier_id) === String(sup.id) ? 'bg-blue-50/80 font-semibold text-[#2563EB]' : 'text-slate-700'">
                                                        <span class="truncate" x-text="sup.name"></span>
                                                        <span class="font-mono text-[11px] text-slate-400 ml-2 shrink-0" x-text="sup.code"></span>
                                                    </button>
                                                </template>
                                                <div x-show="filterSuppliers(q).length === 0" class="px-3.5 py-4 text-center text-slate-400">
                                                    No matching suppliers found.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('supplier_id')" class="mt-1" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Purchase Price -->
                                <div>
                                    <label for="modal_purchase_price" class="block text-sm font-semibold text-slate-700 mb-1">
                                        Purchase Price (Rp) <span class="text-[#E11D48]">*</span>
                                    </label>
                                    <input type="number" id="modal_purchase_price" name="purchase_price" x-model="form.purchase_price" min="0" step="1" required
                                           placeholder="12000000"
                                           class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                    <x-input-error :messages="$errors->get('purchase_price')" class="mt-1" />
                                </div>

                                <!-- Selling Price -->
                                <div>
                                    <label for="modal_selling_price" class="block text-sm font-semibold text-slate-700 mb-1">
                                        Selling Price (Rp) <span class="text-[#E11D48]">*</span>
                                    </label>
                                    <input type="number" id="modal_selling_price" name="selling_price" x-model="form.selling_price" min="0" step="1" required
                                           placeholder="13999000"
                                           class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                    <x-input-error :messages="$errors->get('selling_price')" class="mt-1" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <!-- Stock -->
                                <div>
                                    <label for="modal_stock" class="block text-sm font-semibold text-slate-700 mb-1">
                                        Stock Quantity <span class="text-[#E11D48]">*</span>
                                    </label>
                                    <input type="number" id="modal_stock" name="stock" x-model="form.stock" min="0" required
                                           class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                    <x-input-error :messages="$errors->get('stock')" class="mt-1" />
                                </div>

                                <!-- Min Stock -->
                                <div>
                                    <label for="modal_min_stock" class="block text-sm font-semibold text-slate-700 mb-1">
                                        Minimum Stock <span class="text-[#E11D48]">*</span>
                                    </label>
                                    <input type="number" id="modal_min_stock" name="min_stock" x-model="form.min_stock" min="0" required
                                           class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                    <x-input-error :messages="$errors->get('min_stock')" class="mt-1" />
                                </div>

                                <!-- Unit of Measure Custom Searchable Dropdown -->
                                <div x-data="{ open: false, q: '' }">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                                        Unit of Measure <span class="text-[#E11D48]">*</span>
                                    </label>
                                    <input type="hidden" name="unit" :value="form.unit" required>

                                    <div class="relative">
                                        <button type="button"
                                                @click="open = !open; if (open) $nextTick(() => $refs.modalUnitInput.focus())"
                                                class="w-full rounded-xl border border-[#E2E8F0] bg-white text-sm pl-3.5 pr-9 py-2.5 text-left relative flex items-center justify-between focus:outline-none focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB] transition">
                                            <span class="truncate text-slate-900 font-medium" x-text="form.unit || 'Unit'"></span>
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
                                                <input type="text" x-ref="modalUnitInput" x-model="q"
                                                       placeholder="Search unit..."
                                                       class="w-full rounded-lg border-slate-200 text-xs px-3 py-1.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                            </div>
                                            <div class="max-h-52 overflow-y-auto divide-y divide-slate-50 text-xs">
                                                <template x-for="u in filterUnits(q)" :key="u.value">
                                                    <button type="button"
                                                            @click="form.unit = u.value; open = false; q = ''"
                                                            class="w-full px-3.5 py-2.5 text-left hover:bg-blue-50/60 flex items-center justify-between transition"
                                                            :class="form.unit === u.value ? 'bg-blue-50/80 font-semibold text-[#2563EB]' : 'text-slate-700'">
                                                        <span class="truncate" x-text="u.label"></span>
                                                    </button>
                                                </template>
                                                <div x-show="filterUnits(q).length === 0" class="px-3.5 py-4 text-center text-slate-400">
                                                    No matching unit found.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('unit')" class="mt-1" />
                                </div>
                            </div>

                            <div class="pt-4 border-t border-[#E2E8F0] flex items-center justify-end gap-3">
                                <button type="button" @click="modalOpen = false"
                                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-5 py-2.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition"
                                        x-text="modalMode === 'edit' ? 'Save Changes' : 'Create Product'">
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
