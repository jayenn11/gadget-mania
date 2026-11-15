<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <div>
                <h1 class="text-lg font-bold text-slate-900">Stock Movements</h1>
                <p class="text-xs text-slate-500">Complete inventory audit log for Stock In (PO receipts) and Stock Out (retail sales)</p>
            </div>
        </div>
    </x-slot>

    <div x-data="{
            search: @js(request('search', '')),
            type: @js((string) request('type', '')),
            start_date: @js(request('start_date', '')),
            end_date: @js(request('end_date', '')),
            isLoading: false,
            baseUrl: @js(route('stock-movements.index')),
            movementTypeOptions: [
                { value: '', label: 'All Movements' },
                { value: 'in', label: 'Stock In (IN)' },
                { value: 'out', label: 'Stock Out (OUT)' }
            ],
            findMovementTypeLabel(val) {
                const found = this.movementTypeOptions.find(o => o.value === String(val || ''));
                return found ? found.label : 'All Movements';
            },
            filterMovementTypes(q = '') {
                const query = (q || '').toLowerCase().trim();
                if (!query) return this.movementTypeOptions;
                return this.movementTypeOptions.filter(o => o.label.toLowerCase().includes(query));
            },
            async applyFilter(pageUrl = null) {
                this.isLoading = true;
                try {
                    const url = new URL(pageUrl || this.baseUrl, window.location.origin);
                    if (!pageUrl) {
                        if (this.search) url.searchParams.set('search', this.search);
                        else url.searchParams.delete('search');

                        if (this.type) url.searchParams.set('type', this.type);
                        else url.searchParams.delete('type');

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
                this.type = '';
                this.start_date = '';
                this.end_date = '';
                this.applyFilter();
            }
        }"
        class="space-y-6">

        <!-- Live Search & Auto-Filter Bar -->
        <div class="bg-white border border-[#E2E8F0] rounded-2xl p-4 sm:p-5 shadow-xs">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                <!-- Search Input -->
                <div class="sm:col-span-4 relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text"
                           x-model="search"
                           x-on:input.debounce.350ms="applyFilter()"
                           placeholder="Search reference code, product, or SKU..."
                           class="w-full rounded-xl border-slate-200 text-sm pl-9 pr-3.5 py-2 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                </div>

                <!-- Custom Searchable Dropdown for Movement Type -->
                <div class="sm:col-span-3" x-data="{ open: false, q: '' }">
                    <input type="hidden" name="type" :value="type">
                    <div class="relative">
                        <button type="button"
                                @click="open = !open; if (open) $nextTick(() => $refs.movementTypeInput.focus())"
                                class="w-full rounded-xl border border-slate-200 bg-white text-sm pl-3.5 pr-9 py-2 text-left relative flex items-center justify-between focus:outline-none focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB] transition">
                            <span class="truncate"
                                  :class="type ? 'text-slate-900 font-medium' : 'text-slate-600'"
                                  x-text="findMovementTypeLabel(type)"></span>
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
                                <input type="text" x-ref="movementTypeInput" x-model="q"
                                       placeholder="Search movement type..."
                                       class="w-full rounded-lg border-slate-200 text-xs px-3 py-1.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                            </div>
                            <div class="max-h-52 overflow-y-auto divide-y divide-slate-50 text-xs">
                                <template x-for="opt in filterMovementTypes(q)" :key="opt.value">
                                    <button type="button"
                                            @click="type = opt.value; open = false; q = ''; applyFilter()"
                                            class="w-full px-3.5 py-2.5 text-left hover:bg-blue-50/60 flex items-center justify-between transition"
                                            :class="type === opt.value ? 'bg-blue-50/80 font-semibold text-[#2563EB]' : 'text-slate-700'">
                                        <span class="truncate" x-text="opt.label"></span>
                                    </button>
                                </template>
                                <div x-show="filterMovementTypes(q).length === 0" class="px-3.5 py-4 text-center text-slate-400">
                                    No matching movement type.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Start Date -->
                <div class="sm:col-span-2">
                    <input type="date"
                           x-model="start_date"
                           x-on:change="applyFilter()"
                           title="Start Date"
                           class="w-full rounded-xl border-slate-200 text-sm px-3.5 py-2 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                </div>

                <!-- End Date + Reset -->
                <div class="sm:col-span-3 flex items-center gap-2">
                    <input type="date"
                           x-model="end_date"
                           x-on:change="applyFilter()"
                           title="End Date"
                           class="w-full rounded-xl border-slate-200 text-sm px-3.5 py-2 focus:border-[#2563EB] focus:ring-[#2563EB]" />

                    <button type="button"
                            x-show="search !== '' || type !== '' || start_date !== '' || end_date !== ''"
                            x-cloak
                            @click="resetFilters()"
                            class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 shrink-0 transition">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Stock Movements Table Container -->
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
                            <th class="py-3.5 px-4 font-semibold text-center whitespace-nowrap">Type</th>
                            <th class="py-3.5 px-4 font-semibold text-center whitespace-nowrap">Qty</th>
                            <th class="py-3.5 px-4 font-semibold text-right whitespace-nowrap">Unit Price</th>
                            <th class="py-3.5 px-4 font-semibold text-right whitespace-nowrap">Total Price</th>
                            <th class="py-3.5 px-4 font-semibold whitespace-nowrap">Recorded By</th>
                            <th class="py-3.5 px-4 font-semibold">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($movements as $movement)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 font-mono text-xs font-bold text-slate-900 whitespace-nowrap">
                                    {{ $movement->reference_code }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600 whitespace-nowrap">
                                    {{ $movement->transaction_date?->format('d M Y, H:i') }}
                                </td>
                                <td class="py-3.5 px-4 min-w-[240px]">
                                    <div class="font-semibold text-slate-900">{{ $movement->product->name ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $movement->product->sku ?? '' }} &bull; {{ $movement->product->category->name ?? '' }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    @if($movement->type === 'in')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-[#059669] border border-emerald-200">
                                            STOCK IN
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-[#2563EB] border border-blue-200">
                                            STOCK OUT
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap font-bold {{ $movement->type === 'in' ? 'text-[#059669]' : 'text-[#2563EB]' }}">
                                    {{ $movement->type === 'in' ? '+' : '-' }}{{ $movement->quantity }} {{ $movement->product->unit ?? 'Unit' }}
                                </td>
                                <td class="py-3.5 px-4 text-right text-xs text-slate-600 whitespace-nowrap">
                                    Rp {{ number_format((float) $movement->unit_price, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-semibold text-slate-900 whitespace-nowrap">
                                    Rp {{ number_format((float) $movement->total_price, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-700 whitespace-nowrap">
                                    {{ $movement->user->name ?? '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-500 max-w-xs truncate">
                                    {{ $movement->notes ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-10 px-4 text-center text-sm text-slate-500">
                                    No stock movement records matched your filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($movements->hasPages())
                <div class="px-6 py-4 border-t border-[#E2E8F0] bg-slate-50/50">
                    {{ $movements->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
