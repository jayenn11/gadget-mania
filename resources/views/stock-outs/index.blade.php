<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <div>
                <h1 class="text-lg font-bold text-slate-900">Sales / Stock Out</h1>
                <p class="text-xs text-slate-500">Record retail sales transactions, outgoing inventory, and real-time stock deductions</p>
            </div>
        </div>
    </x-slot>

    @php
        $catalogProducts = $products->map(fn ($p) => [
            'id' => (string) $p->id,
            'sku' => $p->sku,
            'name' => $p->name,
            'brand' => $p->brand,
            'selling_price' => (float) $p->selling_price,
            'stock' => (int) $p->stock,
            'min_stock' => (int) $p->min_stock,
            'unit' => $p->unit,
        ])->values();

        $defaultItems = [
            ['product_id' => '', 'quantity' => 1, 'unit_price' => 0],
        ];

        $initialItems = old('items', $defaultItems);
        $initialModalOpen = $errors->any() || request()->boolean('create');
    @endphp

    <div x-data="{
            search: @js(request('search', '')),
            start_date: @js(request('start_date', '')),
            end_date: @js(request('end_date', '')),
            isLoading: false,
            modalOpen: @js($initialModalOpen),
            baseUrl: @js(route('stock-outs.index')),
            productsCatalog: @js($catalogProducts),
            form: {
                notes: @js(old('notes', '')),
                items: @js($initialItems)
            },
            openCreateModal() {
                this.form = {
                    notes: '',
                    items: [
                        { product_id: '', quantity: 1, unit_price: 0 }
                    ]
                };
                this.modalOpen = true;
            },
            addItem() {
                this.form.items.push({ product_id: '', quantity: 1, unit_price: 0 });
            },
            removeItem(index) {
                if (this.form.items.length > 1) {
                    this.form.items.splice(index, 1);
                }
            },
            findProduct(productId) {
                return this.productsCatalog.find(p => p.id === String(productId || '')) || null;
            },
            availableProductsForRow(rowIndex, searchQuery = '') {
                const q = (searchQuery || '').toLowerCase().trim();
                return this.productsCatalog.filter(prod => {
                    const selectedInOtherRow = this.form.items.some((otherItem, otherIdx) =>
                        otherIdx !== rowIndex && String(otherItem.product_id) === String(prod.id)
                    );
                    if (selectedInOtherRow) return false;
                    if (!q) return true;
                    return prod.name.toLowerCase().includes(q)
                        || prod.sku.toLowerCase().includes(q)
                        || prod.brand.toLowerCase().includes(q);
                });
            },
            selectProductForRow(rowIndex, prod) {
                if (prod.stock <= 0) return;
                this.form.items[rowIndex].product_id = String(prod.id);
                this.form.items[rowIndex].unit_price = prod.selling_price;
                if (this.form.items[rowIndex].quantity > prod.stock && prod.stock > 0) {
                    this.form.items[rowIndex].quantity = prod.stock;
                }
            },
            getMaxStock(productId) {
                const prod = this.findProduct(productId);
                return prod ? prod.stock : 999999;
            },
            isExceedingStock(item) {
                const prod = this.findProduct(item.product_id);
                if (!prod) return false;
                return (parseInt(item.quantity) || 0) > prod.stock;
            },
            get hasAnyStockError() {
                return this.form.items.some(item => this.isExceedingStock(item));
            },
            itemSubtotal(item) {
                const qty = parseFloat(item.quantity) || 0;
                const price = parseFloat(item.unit_price) || 0;
                return qty * price;
            },
            get grandTotal() {
                return this.form.items.reduce((sum, item) => sum + this.itemSubtotal(item), 0);
            },
            formatCurrency(amount) {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(amount || 0));
            },
            async applyFilter(pageUrl = null) {
                this.isLoading = true;
                try {
                    const url = new URL(pageUrl || this.baseUrl, window.location.origin);
                    if (!pageUrl) {
                        if (this.search) url.searchParams.set('search', this.search);
                        else url.searchParams.delete('search');

                        if (this.start_date) url.searchParams.set('start_date', this.start_date);
                        else url.searchParams.delete('start_date');

                        if (this.end_date) url.searchParams.set('end_date', this.end_date);
                        else url.searchParams.delete('end_date');
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
                this.start_date = '';
                this.end_date = '';
                this.applyFilter();
            }
        }"
        @keydown.escape.window="modalOpen = false"
        class="space-y-6">

        <!-- Action & Live Filter Bar -->
        <div class="bg-white border border-[#E2E8F0] rounded-2xl p-4 sm:p-5 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                    <!-- Search Input -->
                    <div class="sm:col-span-5 relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text"
                               x-model="search"
                               x-on:input.debounce.350ms="applyFilter()"
                               placeholder="Search reference code, product, SKU, or notes..."
                               class="w-full rounded-xl border-slate-200 text-sm pl-9 pr-3.5 py-2 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <!-- Date Range Filters -->
                    <div class="sm:col-span-3">
                        <input type="date"
                               x-model="start_date"
                               x-on:change="applyFilter()"
                               title="Start Date"
                               class="w-full rounded-xl border-slate-200 text-sm px-3.5 py-2 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <div class="sm:col-span-4 flex items-center gap-2">
                        <input type="date"
                               x-model="end_date"
                               x-on:change="applyFilter()"
                               title="End Date"
                               class="w-full rounded-xl border-slate-200 text-sm px-3.5 py-2 focus:border-[#2563EB] focus:ring-[#2563EB]" />

                        <button type="button"
                                x-show="search !== '' || start_date !== '' || end_date !== ''"
                                x-cloak
                                @click="resetFilters()"
                                class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 shrink-0 transition">
                            Reset
                        </button>
                    </div>
                </div>

                <button type="button"
                        @click="openCreateModal()"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Record Sale / Stock Out</span>
                </button>
            </div>
        </div>

        <!-- Sales / Stock Out Table Container -->
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
                            <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Reference Code</th>
                            <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Date & Time</th>
                            <th class="py-3.5 px-4 font-semibold min-w-[240px]">Product & SKU</th>
                            <th class="py-3.5 px-4 font-semibold text-center whitespace-nowrap">Qty</th>
                            <th class="py-3.5 px-4 font-semibold text-right whitespace-nowrap">Unit Price</th>
                            <th class="py-3.5 px-4 font-semibold text-right whitespace-nowrap">Total Price</th>
                            <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Cashier / User</th>
                            <th class="py-3.5 px-4 font-semibold">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($stockOuts as $sale)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 font-mono text-xs font-bold text-slate-900 whitespace-nowrap">
                                    {{ $sale->reference_code }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600 whitespace-nowrap">
                                    {{ $sale->transaction_date?->format('d M Y, H:i') }}
                                </td>
                                <td class="py-3.5 px-4 min-w-[240px]">
                                    <div class="font-semibold text-slate-900">{{ $sale->product->name ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $sale->product->sku ?? '' }} &bull; {{ $sale->product->brand ?? '' }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-[#2563EB]">
                                        -{{ $sale->quantity }} {{ $sale->product->unit ?? 'Unit' }}
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
                                <td class="py-3.5 px-4 text-xs text-slate-500 max-w-xs truncate">
                                    {{ $sale->notes ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 px-4 text-center text-sm text-slate-500">
                                    No Sales / Stock Out records matched your filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($stockOuts->hasPages())
                <div class="px-6 py-4 border-t border-[#E2E8F0] bg-slate-50/50">
                    {{ $stockOuts->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>

        <!-- Record Sale / Stock Out Modal (max-w-4xl, overflow-visible) -->
        <div x-show="modalOpen" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="modalOpen"
                     x-transition.opacity
                     @click="modalOpen = false"
                     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>

                <div x-show="modalOpen"
                     x-transition
                     class="relative w-full max-w-4xl bg-white border border-[#E2E8F0] rounded-2xl shadow-xl p-6 sm:p-8 z-10 my-8 overflow-visible">
                <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Record Sale / Stock Out</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Select products sold to customers. Quantities cannot exceed available real-time stock.</p>
                    </div>
                    <button type="button" @click="modalOpen = false"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('stock-outs.store') }}" class="space-y-5 mt-5">
                    @csrf

                    <x-input-error :messages="$errors->get('items')" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs font-semibold text-[#E11D48]" />

                    <!-- Transaction Notes -->
                    <div>
                        <label for="sale_notes" class="block text-sm font-semibold text-slate-700 mb-1">
                            Customer / Transaction Notes <span class="text-xs font-normal text-slate-400">(Optional)</span>
                        </label>
                        <input type="text" id="sale_notes" name="notes" x-model="form.notes"
                               placeholder="e.g. Retail store sale — Walk-in Customer / Invoice #INV-101"
                               class="w-full rounded-xl border-slate-200 text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                        <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                    </div>

                    <!-- Dynamic Multi-Item Sale Builder -->
                    <div class="border border-[#E2E8F0] rounded-2xl overflow-visible">
                        <div class="px-4 py-3 bg-slate-50 border-b border-[#E2E8F0] rounded-t-2xl flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Sale Line Items <span class="text-[#E11D48]">*</span></h3>
                            <button type="button" @click="addItem()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-[#2563EB] text-xs font-semibold transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Add Item</span>
                            </button>
                        </div>

                        <div class="p-4 space-y-3">
                            <template x-for="(item, index) in form.items" :key="index">
                                <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-200/70 space-y-2">
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-start">
                                        <!-- Type 2: Searchable Product Select (showing available stock & preventing duplicates) -->
                                        <div class="sm:col-span-5" x-data="{ openProd: false, prodQuery: '' }">
                                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                                Product <span class="text-[#E11D48]">*</span>
                                            </label>
                                            <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id" required>

                                            <div class="relative">
                                                <button type="button"
                                                        @click="openProd = !openProd; if (openProd) $nextTick(() => $refs.saleProdSearchInput.focus())"
                                                        class="w-full rounded-xl border border-slate-200 bg-white text-sm pl-3 pr-9 py-2 text-left relative flex items-center justify-between focus:outline-none focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB] transition">
                                                    <span class="truncate"
                                                          :class="item.product_id ? 'text-slate-900 font-medium' : 'text-slate-400'"
                                                          x-text="findProduct(item.product_id) ? `${findProduct(item.product_id).name} (Stock: ${findProduct(item.product_id).stock})` : '-- Select Product --'"></span>
                                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </span>
                                                </button>

                                                <!-- Searchable Dropdown Menu -->
                                                <div x-show="openProd" x-cloak @click.outside="openProd = false"
                                                     x-transition
                                                     class="absolute z-[60] mt-1.5 w-full bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                                                    <div class="p-2 border-b border-slate-100 bg-slate-50/70">
                                                        <input type="text" x-ref="saleProdSearchInput" x-model="prodQuery"
                                                               placeholder="Search product name or SKU..."
                                                               class="w-full rounded-lg border-slate-200 text-xs px-3 py-1.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                                    </div>
                                                    <div class="max-h-56 overflow-y-auto divide-y divide-slate-50 text-xs">
                                                        <template x-for="prod in availableProductsForRow(index, prodQuery)" :key="prod.id">
                                                            <button type="button"
                                                                    :disabled="prod.stock <= 0"
                                                                    @click="selectProductForRow(index, prod); openProd = false; prodQuery = ''"
                                                                    class="w-full px-3.5 py-2.5 text-left flex items-center justify-between gap-2 transition"
                                                                    :class="prod.stock <= 0 ? 'opacity-40 cursor-not-allowed bg-slate-50' : (String(item.product_id) === String(prod.id) ? 'bg-blue-50/80 font-semibold text-[#2563EB]' : 'hover:bg-blue-50/60 text-slate-700')">
                                                                <div class="min-w-0">
                                                                    <div class="truncate font-medium" x-text="prod.name"></div>
                                                                    <div class="text-[11px] text-slate-400 font-mono" x-text="`${prod.sku} • ${prod.brand}`"></div>
                                                                </div>
                                                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold shrink-0"
                                                                      :class="prod.stock <= prod.min_stock ? 'bg-rose-50 text-[#E11D48]' : 'bg-emerald-50 text-[#059669]'"
                                                                      x-text="`Stock: ${prod.stock}`"></span>
                                                            </button>
                                                        </template>
                                                        <div x-show="availableProductsForRow(index, prodQuery).length === 0" class="px-3.5 py-4 text-center text-slate-400">
                                                            No available products match your search.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Quantity -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                                Quantity <span class="text-[#E11D48]">*</span>
                                            </label>
                                            <input type="number"
                                                   :name="`items[${index}][quantity]`"
                                                   x-model.number="item.quantity"
                                                   min="1"
                                                   :max="getMaxStock(item.product_id)"
                                                   required
                                                   :class="isExceedingStock(item) ? 'border-[#E11D48] focus:border-[#E11D48] focus:ring-[#E11D48]' : 'border-slate-200 focus:border-[#2563EB] focus:ring-[#2563EB]'"
                                                   class="w-full rounded-xl bg-white text-sm px-3 py-2" />
                                        </div>

                                        <!-- Unit Selling Price -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                                Selling Price (Rp) <span class="text-[#E11D48]">*</span>
                                            </label>
                                            <input type="number"
                                                   :name="`items[${index}][unit_price]`"
                                                   x-model.number="item.unit_price"
                                                   min="0"
                                                   step="1"
                                                   required
                                                   class="w-full rounded-xl border-slate-200 bg-white text-sm px-3 py-2 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                        </div>

                                        <!-- Subtotal -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-semibold text-slate-600 mb-1">Subtotal</label>
                                            <div class="px-3 py-2 rounded-xl bg-slate-100 text-xs font-bold text-slate-800 truncate"
                                                 x-text="formatCurrency(itemSubtotal(item))"></div>
                                        </div>

                                        <!-- Remove Row -->
                                        <div class="sm:col-span-1 flex sm:justify-end pt-5">
                                            <button type="button"
                                                    @click="removeItem(index)"
                                                    :disabled="form.items.length === 1"
                                                    :class="form.items.length === 1 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-rose-50 text-[#E11D48]'"
                                                    class="p-2 rounded-lg border border-rose-200 transition"
                                                    title="Remove item">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Real-time Stock Exceeded Warning -->
                                    <div x-show="isExceedingStock(item)" x-cloak class="text-xs font-semibold text-[#E11D48]">
                                        Requested quantity exceeds available stock (<span x-text="getMaxStock(item.product_id)"></span> available).
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Grand Total Footer -->
                        <div class="px-4 py-3.5 bg-slate-50 border-t border-[#E2E8F0] rounded-b-2xl flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Grand Total Sale</span>
                            <span class="text-lg font-bold text-[#2563EB]" x-text="formatCurrency(grandTotal)"></span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-[#E2E8F0] flex items-center justify-end gap-3">
                        <button type="button" @click="modalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                                :disabled="hasAnyStockError"
                                :class="hasAnyStockError ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-[#2563EB] hover:bg-blue-700'"
                                class="px-5 py-2.5 rounded-xl text-white text-xs font-semibold shadow-xs transition">
                            Complete Sale & Deduct Stock
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
