<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <div>
                <h1 class="text-lg font-bold text-slate-900">Purchase Orders</h1>
                <p class="text-xs text-slate-500">Manage supplier procurement orders, multi-item restocking, and goods receipt workflow</p>
            </div>
        </div>
    </x-slot>

    @php
        $catalogSuppliers = $suppliers->map(fn ($s) => [
            'id' => (string) $s->id,
            'code' => $s->code,
            'name' => $s->name,
            'contact_person' => $s->contact_person,
        ])->values();

        $catalogProducts = $products->map(fn ($p) => [
            'id' => (string) $p->id,
            'sku' => $p->sku,
            'name' => $p->name,
            'brand' => $p->brand,
            'supplier_id' => $p->supplier_id ? (string) $p->supplier_id : '',
            'purchase_price' => (float) $p->purchase_price,
            'stock' => (int) $p->stock,
            'min_stock' => (int) $p->min_stock,
            'unit' => $p->unit,
        ])->values();

        $statusOptions = [
            ['value' => '', 'label' => 'All Statuses'],
            ['value' => 'pending', 'label' => 'Pending'],
            ['value' => 'processing', 'label' => 'Processing'],
            ['value' => 'received', 'label' => 'Received'],
            ['value' => 'cancelled', 'label' => 'Cancelled'],
        ];

        $preselectedProductId = (string) request('product_id', '');
        $preselectedProduct = $preselectedProductId
            ? $products->firstWhere('id', (int) $preselectedProductId)
            : null;

        $preselectedSupplierId = $preselectedProduct && $preselectedProduct->supplier_id
            ? (string) $preselectedProduct->supplier_id
            : (string) request('supplier_id', '');

        $autoCalculatedQty = $preselectedProduct
            ? max(1, ($preselectedProduct->min_stock - $preselectedProduct->stock) + 1)
            : 1;

        $defaultItems = [
            [
                'product_id' => $preselectedProduct ? (string) $preselectedProduct->id : '',
                'quantity' => $autoCalculatedQty,
                'unit_price' => $preselectedProduct ? (float) $preselectedProduct->purchase_price : 0,
            ],
        ];

        $initialItems = old('items', $defaultItems);
        $initialModalOpen = $errors->any() || request()->boolean('create') || $preselectedProduct !== null;
    @endphp

    <div x-data="{
            search: @js(request('search', '')),
            status: @js((string) request('status', '')),
            isLoading: false,
            modalOpen: @js($initialModalOpen),
            baseUrl: @js(route('purchase-orders.index')),
            suppliersCatalog: @js($catalogSuppliers),
            productsCatalog: @js($catalogProducts),
            statusOptions: @js($statusOptions),
            form: {
                supplier_id: @js((string) old('supplier_id', $preselectedSupplierId)),
                order_date: @js(old('order_date', now()->toDateString())),
                notes: @js(old('notes', '')),
                items: @js($initialItems)
            },
            detailModal: {
                open: false,
                po: null
            },
            workflowModal: {
                open: false,
                action: '',
                method: 'PATCH',
                title: '',
                description: '',
                confirmLabel: 'Confirm',
                confirmStyle: 'bg-[#2563EB] hover:bg-blue-700',
                iconStyle: 'bg-blue-50 border-blue-100 text-[#2563EB]'
            },
            openCreateModal() {
                this.form = {
                    supplier_id: '',
                    order_date: '{{ now()->toDateString() }}',
                    notes: '',
                    items: [
                        { product_id: '', quantity: 1, unit_price: 0 }
                    ]
                };
                this.modalOpen = true;
            },
            openDetailModal(poData) {
                this.detailModal.po = poData;
                this.detailModal.open = true;
            },
            addItem() {
                this.form.items.push({ product_id: '', quantity: 1, unit_price: 0 });
            },
            removeItem(index) {
                if (this.form.items.length > 1) {
                    this.form.items.splice(index, 1);
                }
            },
            findStatusLabel(val) {
                const found = this.statusOptions.find(o => o.value === String(val || ''));
                return found ? found.label : 'All Statuses';
            },
            filterStatuses(q = '') {
                const query = (q || '').toLowerCase().trim();
                if (!query) return this.statusOptions;
                return this.statusOptions.filter(o => o.label.toLowerCase().includes(query));
            },
            findSupplier(supplierId) {
                return this.suppliersCatalog.find(s => s.id === String(supplierId || '')) || null;
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
            filterSuppliers(searchQuery = '') {
                const q = (searchQuery || '').toLowerCase().trim();
                if (!q) return this.suppliersCatalog;
                return this.suppliersCatalog.filter(sup =>
                    sup.name.toLowerCase().includes(q) || sup.code.toLowerCase().includes(q)
                );
            },
            selectProductForRow(rowIndex, prod) {
                this.form.items[rowIndex].product_id = String(prod.id);
                this.form.items[rowIndex].unit_price = prod.purchase_price;
                if (!this.form.supplier_id && prod.supplier_id) {
                    this.form.supplier_id = String(prod.supplier_id);
                }
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
            openWorkflowModal(config) {
                this.workflowModal = {
                    open: true,
                    action: config.action,
                    method: config.method || 'PATCH',
                    title: config.title,
                    description: config.description,
                    confirmLabel: config.confirmLabel,
                    confirmStyle: config.confirmStyle || 'bg-[#2563EB] hover:bg-blue-700',
                    iconStyle: config.iconStyle || 'bg-blue-50 border-blue-100 text-[#2563EB]'
                };
            },
            async applyFilter(pageUrl = null) {
                this.isLoading = true;
                try {
                    const url = new URL(pageUrl || this.baseUrl, window.location.origin);
                    if (!pageUrl) {
                        url.searchParams.delete('create');
                        url.searchParams.delete('product_id');
                        url.searchParams.delete('supplier_id');

                        if (this.search) url.searchParams.set('search', this.search);
                        else url.searchParams.delete('search');

                        if (this.status) url.searchParams.set('status', this.status);
                        else url.searchParams.delete('status');
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
                this.status = '';
                this.applyFilter();
            }
        }"
        @keydown.escape.window="modalOpen = false; detailModal.open = false; workflowModal.open = false"
        class="space-y-6">

        <!-- Action & Live Filter Bar -->
        <div class="bg-white border border-[#E2E8F0] rounded-2xl p-4 sm:p-5 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex-1 flex flex-wrap items-center gap-3 max-w-2xl">
                    <div class="relative flex-1 min-w-[240px]">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text"
                               x-model="search"
                               x-on:input.debounce.350ms="applyFilter()"
                               placeholder="Search PO number, supplier name, or notes..."
                               class="w-full rounded-xl border-slate-200 text-sm pl-9 pr-3.5 py-2 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <!-- Custom Searchable Dropdown for Status Auto-Filter -->
                    <div class="w-48" x-data="{ open: false, q: '' }">
                        <input type="hidden" name="status" :value="status">
                        <div class="relative">
                            <button type="button"
                                    @click="open = !open; if (open) $nextTick(() => $refs.statusFilterInput.focus())"
                                    class="w-full rounded-xl border border-slate-200 bg-white text-sm pl-3.5 pr-9 py-2 text-left relative flex items-center justify-between focus:outline-none focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB] transition">
                                <span class="truncate"
                                      :class="status ? 'text-slate-900 font-medium' : 'text-slate-600'"
                                      x-text="findStatusLabel(status)"></span>
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
                                    <input type="text" x-ref="statusFilterInput" x-model="q"
                                           placeholder="Search status..."
                                           class="w-full rounded-lg border-slate-200 text-xs px-3 py-1.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                </div>
                                <div class="max-h-52 overflow-y-auto divide-y divide-slate-50 text-xs">
                                    <template x-for="opt in filterStatuses(q)" :key="opt.value">
                                        <button type="button"
                                                @click="status = opt.value; open = false; q = ''; applyFilter()"
                                                class="w-full px-3.5 py-2.5 text-left hover:bg-blue-50/60 flex items-center justify-between transition"
                                                :class="status === opt.value ? 'bg-blue-50/80 font-semibold text-[#2563EB]' : 'text-slate-700'">
                                            <span class="truncate" x-text="opt.label"></span>
                                        </button>
                                    </template>
                                    <div x-show="filterStatuses(q).length === 0" class="px-3.5 py-4 text-center text-slate-400">
                                        No matching status found.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button"
                            x-show="search !== '' || status !== ''"
                            x-cloak
                            @click="resetFilters()"
                            class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                        Reset
                    </button>
                </div>

                <button type="button"
                        @click="openCreateModal()"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Create Purchase Order</span>
                </button>
            </div>
        </div>

        <!-- Purchase Orders Table Container -->
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
                            <th class="py-3.5 px-4 font-semibold whitespace-nowrap">PO Number</th>
                            <th class="py-3.5 px-4 font-semibold min-w-[200px]">Supplier</th>
                            <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Order Date</th>
                            <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Created By</th>
                            <th class="py-3.5 px-4 font-semibold text-center whitespace-nowrap">Items</th>
                            <th class="py-3.5 px-4 font-semibold text-right whitespace-nowrap">Total Amount</th>
                            <th class="py-3.5 px-4 font-semibold text-center whitespace-nowrap">Status</th>
                            <th class="py-3.5 px-4 font-semibold text-right whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($purchaseOrders as $po)
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

                                $poDetailPayload = [
                                    'id' => $po->id,
                                    'po_number' => $po->po_number,
                                    'order_date' => $po->order_date?->format('d M Y'),
                                    'created_by' => $po->user->name ?? '-',
                                    'status' => $normalizedStatus,
                                    'status_label' => $badge['label'],
                                    'status_style' => $badge['style'],
                                    'supplier_name' => $po->supplier->name ?? '-',
                                    'supplier_code' => $po->supplier->code ?? '',
                                    'supplier_pic' => $po->supplier->contact_person ?? '-',
                                    'supplier_phone' => $po->supplier->phone ?? '-',
                                    'notes' => $po->notes ?: 'No additional notes.',
                                    'total_amount' => (float) $po->total_amount,
                                    'show_url' => route('purchase-orders.show', $po),
                                    'process_url' => route('purchase-orders.process', $po),
                                    'receive_url' => route('purchase-orders.receive', $po),
                                    'cancel_url' => route('purchase-orders.cancel', $po),
                                    'items' => $po->items->map(fn ($item) => [
                                        'sku' => $item->product->sku ?? '-',
                                        'name' => $item->product->name ?? '-',
                                        'brand' => $item->product->brand ?? '-',
                                        'unit' => $item->product->unit ?? 'Unit',
                                        'quantity' => (int) $item->quantity,
                                        'unit_price' => (float) $item->unit_price,
                                        'subtotal' => (float) $item->subtotal,
                                    ])->values(),
                                ];
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 font-mono text-xs font-bold text-slate-900 whitespace-nowrap">
                                    <button type="button" @click="openDetailModal(@js($poDetailPayload))" class="hover:text-[#2563EB] hover:underline">
                                        {{ $po->po_number }}
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 min-w-[200px]">
                                    <div class="font-semibold text-slate-900">{{ $po->supplier->name ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $po->supplier->code ?? '' }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600 whitespace-nowrap">
                                    {{ $po->order_date?->format('d M Y') }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-700 whitespace-nowrap">
                                    {{ $po->user->name ?? '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        {{ $po->items->count() }} Items ({{ $po->items->sum('quantity') }} Qty)
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-semibold text-slate-900 whitespace-nowrap">
                                    Rp {{ number_format((float) $po->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badge['style'] }}">
                                        {{ $badge['label'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button"
                                                @click="openDetailModal(@js($poDetailPayload))"
                                                class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-xs font-semibold text-slate-700 transition">
                                            Details
                                        </button>

                                        @if(in_array($normalizedStatus, ['pending', 'processing'], true))
                                            <button type="button"
                                                    @click="openWorkflowModal({
                                                        action: '{{ route('purchase-orders.receive', $po) }}',
                                                        method: 'PATCH',
                                                        title: 'Receive Goods & Update Stock',
                                                        description: 'Confirm receipt of all items in {{ $po->po_number }}? This will automatically increment product stock levels and record Stock In movements.',
                                                        confirmLabel: 'Receive Goods',
                                                        confirmStyle: 'bg-emerald-600 hover:bg-emerald-700',
                                                        iconStyle: 'bg-emerald-50 border-emerald-100 text-emerald-600'
                                                    })"
                                                    class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-xs font-semibold text-white shadow-2xs transition">
                                                Receive Goods
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 px-4 text-center text-sm text-slate-500">
                                    No Purchase Orders matched your filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($purchaseOrders->hasPages())
                <div class="px-6 py-4 border-t border-[#E2E8F0] bg-slate-50/50">
                    {{ $purchaseOrders->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>

        <!-- Purchase Order Detail & Full Status Workflow Modal -->
        <div x-show="detailModal.open" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="detailModal.open"
                     x-transition.opacity
                     @click="detailModal.open = false"
                     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>

                <div x-show="detailModal.open"
                     x-transition
                     class="relative w-full max-w-3xl bg-white border border-[#E2E8F0] rounded-2xl shadow-xl p-6 sm:p-8 z-10 my-8 overflow-visible">
                    <template x-if="detailModal.po">
                        <div class="space-y-5">
                            <!-- Modal Header -->
                            <div class="flex flex-wrap items-start justify-between gap-4 pb-4 border-b border-slate-100">
                                <div>
                                    <div class="flex items-center gap-2.5">
                                        <h2 class="text-lg font-bold font-mono text-slate-900" x-text="detailModal.po.po_number"></h2>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                              :class="detailModal.po.status_style"
                                              x-text="detailModal.po.status_label"></span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Order Date: <span class="font-semibold text-slate-700" x-text="detailModal.po.order_date"></span>
                                        &bull; Created by <span class="font-semibold text-slate-700" x-text="detailModal.po.created_by"></span>
                                    </p>
                                </div>
                                <button type="button" @click="detailModal.open = false"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Supplier & Notes Info -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Supplier</span>
                                    <span class="font-bold text-slate-900 mt-0.5 block" x-text="`${detailModal.po.supplier_name} (${detailModal.po.supplier_code})`"></span>
                                    <span class="text-xs text-slate-600 mt-0.5 block" x-text="`PIC: ${detailModal.po.supplier_pic} • ${detailModal.po.supplier_phone}`"></span>
                                </div>
                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Order Notes</span>
                                    <p class="text-xs text-slate-700 mt-1 leading-relaxed" x-text="detailModal.po.notes"></p>
                                </div>
                            </div>

                            <!-- Ordered Line Items -->
                            <div class="border border-[#E2E8F0] rounded-xl overflow-hidden">
                                <table class="w-full text-left border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-[#E2E8F0]">
                                            <th class="py-2.5 px-4 font-semibold">Product & SKU</th>
                                            <th class="py-2.5 px-4 font-semibold text-center whitespace-nowrap">Qty</th>
                                            <th class="py-2.5 px-4 font-semibold text-right whitespace-nowrap">Unit Price</th>
                                            <th class="py-2.5 px-4 font-semibold text-right whitespace-nowrap">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="(item, idx) in detailModal.po.items" :key="idx">
                                            <tr>
                                                <td class="py-2.5 px-4">
                                                    <div class="font-semibold text-slate-900 text-xs" x-text="item.name"></div>
                                                    <div class="text-[11px] text-slate-500 font-mono" x-text="`${item.sku} • ${item.brand}`"></div>
                                                </td>
                                                <td class="py-2.5 px-4 text-center text-xs font-bold text-slate-900 whitespace-nowrap"
                                                    x-text="`${item.quantity} ${item.unit}`"></td>
                                                <td class="py-2.5 px-4 text-right text-xs text-slate-600 whitespace-nowrap"
                                                    x-text="formatCurrency(item.unit_price)"></td>
                                                <td class="py-2.5 px-4 text-right text-xs font-semibold text-slate-900 whitespace-nowrap"
                                                    x-text="formatCurrency(item.subtotal)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-slate-50 border-t border-[#E2E8F0]">
                                            <td colspan="3" class="py-3 px-4 text-right text-xs font-bold uppercase tracking-wider text-slate-600">Total Amount</td>
                                            <td class="py-3 px-4 text-right text-sm font-bold text-[#2563EB] whitespace-nowrap"
                                                x-text="formatCurrency(detailModal.po.total_amount)"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Full Workflow Actions Footer -->
                            <div class="pt-4 border-t border-[#E2E8F0] flex flex-wrap items-center justify-between gap-3">
                                <a :href="detailModal.po.show_url"
                                   class="text-xs font-semibold text-[#2563EB] hover:underline">
                                    Open Full Page View &rarr;
                                </a>

                                <div class="flex flex-wrap items-center gap-2">
                                    <template x-if="detailModal.po.status === 'pending'">
                                        <button type="button"
                                                @click="detailModal.open = false; openWorkflowModal({
                                                    action: detailModal.po.process_url,
                                                    method: 'PATCH',
                                                    title: 'Mark PO as Processing',
                                                    description: `Update status of ${detailModal.po.po_number} to Processing? This indicates the order has been sent to the supplier.`,
                                                    confirmLabel: 'Mark as Processing',
                                                    confirmStyle: 'bg-[#2563EB] hover:bg-blue-700',
                                                    iconStyle: 'bg-blue-50 border-blue-100 text-[#2563EB]'
                                                })"
                                                class="px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-[#2563EB] text-xs font-semibold transition">
                                            Mark as Processing
                                        </button>
                                    </template>

                                    <template x-if="detailModal.po.status === 'pending' || detailModal.po.status === 'processing'">
                                        <button type="button"
                                                @click="detailModal.open = false; openWorkflowModal({
                                                    action: detailModal.po.receive_url,
                                                    method: 'PATCH',
                                                    title: 'Receive Goods & Update Stock',
                                                    description: `Confirm receipt of all items in ${detailModal.po.po_number}? This will automatically increment product stock levels and record Stock In movements.`,
                                                    confirmLabel: 'Receive Goods (Mark as Received)',
                                                    confirmStyle: 'bg-emerald-600 hover:bg-emerald-700',
                                                    iconStyle: 'bg-emerald-50 border-emerald-100 text-emerald-600'
                                                })"
                                                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition">
                                            Receive Goods (Mark as Received)
                                        </button>
                                    </template>

                                    <template x-if="detailModal.po.status === 'pending' || detailModal.po.status === 'processing'">
                                        <button type="button"
                                                @click="detailModal.open = false; openWorkflowModal({
                                                    action: detailModal.po.cancel_url,
                                                    method: 'PATCH',
                                                    title: 'Cancel Purchase Order',
                                                    description: `Are you sure you want to cancel Purchase Order ${detailModal.po.po_number}? No stock changes will occur.`,
                                                    confirmLabel: 'Cancel Order',
                                                    confirmStyle: 'bg-[#E11D48] hover:bg-rose-700',
                                                    iconStyle: 'bg-rose-50 border-rose-100 text-[#E11D48]'
                                                })"
                                                class="px-3.5 py-2 rounded-xl border border-rose-200 hover:bg-rose-50 text-[#E11D48] text-xs font-semibold transition">
                                            Cancel Order
                                        </button>
                                    </template>

                                    <button type="button" @click="detailModal.open = false"
                                            class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Create Purchase Order Modal (max-w-4xl, overflow-visible) -->
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
                            <h2 class="text-lg font-bold text-slate-900">Create Purchase Order</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Select supplier and add one or more electronics products to order</p>
                        </div>
                        <button type="button" @click="modalOpen = false"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('purchase-orders.store') }}" class="space-y-5 mt-5">
                        @csrf

                        <!-- PO Header Fields -->
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                            <!-- Custom Searchable Dropdown for Supplier -->
                            <div class="sm:col-span-6" x-data="{ open: false, q: '' }">
                                <label class="block text-sm font-semibold text-slate-700 mb-1">
                                    Supplier <span class="text-[#E11D48]">*</span>
                                </label>
                                <input type="hidden" name="supplier_id" :value="form.supplier_id" required>

                                <div class="relative">
                                    <button type="button"
                                            @click="open = !open; if (open) $nextTick(() => $refs.supplierSearchInput.focus())"
                                            class="w-full rounded-xl border border-slate-200 bg-white text-sm pl-3.5 pr-9 py-2.5 text-left relative flex items-center justify-between focus:outline-none focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB] transition">
                                        <span class="truncate"
                                              :class="form.supplier_id ? 'text-slate-900 font-medium' : 'text-slate-400'"
                                              x-text="findSupplier(form.supplier_id) ? `${findSupplier(form.supplier_id).name} (${findSupplier(form.supplier_id).code})` : '-- Select Supplier --'"></span>
                                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </span>
                                    </button>

                                    <!-- Dropdown Panel -->
                                    <div x-show="open" x-cloak @click.outside="open = false"
                                         x-transition
                                         class="absolute z-[60] mt-1.5 w-full bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                                        <div class="p-2 border-b border-slate-100 bg-slate-50/70">
                                            <input type="text" x-ref="supplierSearchInput" x-model="q"
                                                   placeholder="Search supplier name or code..."
                                                   class="w-full rounded-lg border-slate-200 text-xs px-3 py-1.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                        </div>
                                        <div class="max-h-52 overflow-y-auto divide-y divide-slate-50 text-xs">
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

                            <div class="sm:col-span-3">
                                <label for="po_order_date" class="block text-sm font-semibold text-slate-700 mb-1">
                                    Order Date <span class="text-[#E11D48]">*</span>
                                </label>
                                <input type="date" id="po_order_date" name="order_date" x-model="form.order_date" required
                                       class="w-full rounded-xl border-slate-200 text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                <x-input-error :messages="$errors->get('order_date')" class="mt-1" />
                            </div>

                            <div class="sm:col-span-3">
                                <label for="po_notes" class="block text-sm font-semibold text-slate-700 mb-1">
                                    Notes <span class="text-xs font-normal text-slate-400">(Optional)</span>
                                </label>
                                <input type="text" id="po_notes" name="notes" x-model="form.notes"
                                       placeholder="e.g. Urgent restock"
                                       class="w-full rounded-xl border-slate-200 text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                            </div>
                        </div>

                        <!-- Dynamic Multi-Item Builder -->
                        <div class="border border-[#E2E8F0] rounded-2xl overflow-visible">
                            <div class="px-4 py-3 bg-slate-50 border-b border-[#E2E8F0] rounded-t-2xl flex items-center justify-between">
                                <div>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Order Items <span class="text-[#E11D48]">*</span></h3>
                                </div>
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
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-start p-3 rounded-xl bg-slate-50/70 border border-slate-200/70">
                                        <!-- Custom Searchable Product Select (with duplicate prevention) -->
                                        <div class="sm:col-span-5" x-data="{ openProd: false, prodQuery: '' }">
                                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                                Product <span class="text-[#E11D48]">*</span>
                                            </label>
                                            <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id" required>

                                            <div class="relative">
                                                <button type="button"
                                                        @click="openProd = !openProd; if (openProd) $nextTick(() => $refs.prodSearchInput.focus())"
                                                        class="w-full rounded-xl border border-slate-200 bg-white text-sm pl-3 pr-9 py-2 text-left relative flex items-center justify-between focus:outline-none focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB] transition">
                                                    <span class="truncate"
                                                          :class="item.product_id ? 'text-slate-900 font-medium' : 'text-slate-400'"
                                                          x-text="findProduct(item.product_id) ? `${findProduct(item.product_id).name} (${findProduct(item.product_id).sku}) — Stock: ${findProduct(item.product_id).stock}` : '-- Select Product --'"></span>
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
                                                        <input type="text" x-ref="prodSearchInput" x-model="prodQuery"
                                                               placeholder="Search product name or SKU..."
                                                               class="w-full rounded-lg border-slate-200 text-xs px-3 py-1.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                                    </div>
                                                    <div class="max-h-56 overflow-y-auto divide-y divide-slate-50 text-xs">
                                                        <template x-for="prod in availableProductsForRow(index, prodQuery)" :key="prod.id">
                                                            <button type="button"
                                                                    @click="selectProductForRow(index, prod); openProd = false; prodQuery = ''"
                                                                    class="w-full px-3.5 py-2.5 text-left hover:bg-blue-50/60 flex items-center justify-between gap-2 transition"
                                                                    :class="String(item.product_id) === String(prod.id) ? 'bg-blue-50/80 font-semibold text-[#2563EB]' : 'text-slate-700'">
                                                                <div class="min-w-0">
                                                                    <div class="truncate font-medium" x-text="prod.name"></div>
                                                                    <div class="text-[11px] text-slate-400 font-mono" x-text="`${prod.sku} • ${prod.brand}`"></div>
                                                                </div>
                                                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold shrink-0"
                                                                      :class="prod.stock <= prod.min_stock ? 'bg-rose-50 text-[#E11D48]' : 'bg-slate-100 text-slate-600'"
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
                                                   required
                                                   class="w-full rounded-xl border-slate-200 bg-white text-sm px-3 py-2 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                        </div>

                                        <!-- Unit Purchase Price -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                                Unit Price (Rp) <span class="text-[#E11D48]">*</span>
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

                                        <!-- Remove Row Button -->
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
                                </template>
                            </div>

                            <!-- Grand Total Footer -->
                            <div class="px-4 py-3.5 bg-slate-50 border-t border-[#E2E8F0] rounded-b-2xl flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Estimated Grand Total</span>
                                <span class="text-lg font-bold text-[#2563EB]" x-text="formatCurrency(grandTotal)"></span>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-[#E2E8F0] flex items-center justify-end gap-3">
                            <button type="button" @click="modalOpen = false"
                                    class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-5 py-2.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition">
                                Submit Purchase Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Custom PO Workflow Confirmation Modal -->
        <div x-show="workflowModal.open" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="workflowModal.open"
                     x-transition.opacity
                     @click="workflowModal.open = false"
                     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>

                <div x-show="workflowModal.open"
                     x-transition
                     class="relative w-full max-w-md bg-white border border-[#E2E8F0] rounded-2xl shadow-xl p-6 z-10 overflow-visible">
                    <div class="flex items-start justify-between">
                        <div :class="workflowModal.iconStyle"
                             class="w-11 h-11 rounded-2xl border flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <button type="button" @click="workflowModal.open = false"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-4">
                        <h3 class="text-lg font-bold text-slate-900" x-text="workflowModal.title"></h3>
                        <p class="mt-1.5 text-sm text-slate-600 leading-relaxed" x-text="workflowModal.description"></p>
                    </div>

                    <form method="POST" :action="workflowModal.action" class="mt-6 flex items-center justify-end gap-3">
                        @csrf
                        <input type="hidden" name="_method" :value="workflowModal.method">
                        <button type="button" @click="workflowModal.open = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition">
                            Cancel
                        </button>
                        <button type="submit"
                                :class="workflowModal.confirmStyle"
                                class="px-4 py-2.5 rounded-xl text-white text-xs font-semibold shadow-xs transition"
                                x-text="workflowModal.confirmLabel">
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
