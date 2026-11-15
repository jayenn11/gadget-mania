<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-lg font-bold text-slate-900">Supplier Details & PO History</h1>
                <p class="text-xs text-slate-500">{{ $supplier->code }} &bull; {{ $supplier->name }}</p>
            </div>
        </div>
    </x-slot>

    <!-- Supplier Profile Information -->
    <div class="bg-white border border-[#E2E8F0] rounded-2xl p-6 shadow-xs">
        <div class="flex flex-wrap items-start justify-between gap-4 pb-5 border-b border-slate-100">
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-blue-50 text-[#2563EB] mb-2">
                    {{ $supplier->code }}
                </span>
                <h2 class="text-xl font-bold text-slate-900">{{ $supplier->name }}</h2>
                <p class="text-sm text-slate-600 mt-1">{{ $supplier->address }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('suppliers.index') }}"
                   class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    &larr; Back to Suppliers
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-5 text-sm">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-xs text-slate-500 block">Contact Person (PIC)</span>
                <span class="font-semibold text-slate-900 mt-0.5 block">{{ $supplier->contact_person }}</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-xs text-slate-500 block">Phone Number</span>
                <span class="font-semibold text-slate-900 mt-0.5 block">{{ $supplier->phone }}</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-xs text-slate-500 block">Official Email</span>
                <span class="font-semibold text-slate-900 mt-0.5 block">{{ $supplier->email ?: '-' }}</span>
            </div>
        </div>
    </div>

    <!-- Purchase Order History -->
    <div class="bg-white border border-[#E2E8F0] rounded-2xl shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-[#E2E8F0] flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Purchase Order (PO) History</h3>
                <p class="text-xs text-slate-500">Procurement orders placed with {{ $supplier->name }}</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-[#2563EB]">
                Total: {{ $supplier->purchaseOrders->count() }} POs
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider border-b border-[#E2E8F0]">
                        <th class="py-3.5 px-4 font-semibold">PO Number</th>
                        <th class="py-3.5 px-4 font-semibold">Order Date</th>
                        <th class="py-3.5 px-4 font-semibold">Created By</th>
                        <th class="py-3.5 px-4 font-semibold">Items Summary</th>
                        <th class="py-3.5 px-4 font-semibold text-right">Total Amount</th>
                        <th class="py-3.5 px-4 font-semibold text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($supplier->purchaseOrders as $po)
                        @php
                            $statusBadge = match($po->status) {
                                'diterima' => ['label' => 'Received', 'style' => 'bg-emerald-100 text-[#059669]'],
                                'diproses' => ['label' => 'Processing', 'style' => 'bg-blue-100 text-[#2563EB]'],
                                'dibatalkan' => ['label' => 'Cancelled', 'style' => 'bg-rose-100 text-[#E11D48]'],
                                default => ['label' => 'Pending', 'style' => 'bg-amber-100 text-[#D97706]'],
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 font-mono text-xs font-bold text-slate-900 whitespace-nowrap">
                                {{ $po->po_number }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600 whitespace-nowrap">
                                {{ $po->order_date?->format('d M Y') }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-700">
                                {{ $po->user->name ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600">
                                <ul class="space-y-0.5">
                                    @foreach($po->items as $item)
                                        <li>&bull; {{ $item->product->name ?? '-' }} ({{ $item->quantity }} units)</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="py-3.5 px-4 text-right font-semibold text-slate-900 whitespace-nowrap">
                                Rp {{ number_format((float) $po->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $statusBadge['style'] }}">
                                    {{ $statusBadge['label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 px-4 text-center text-sm text-slate-500">
                                No Purchase Orders recorded for this supplier yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Supplied Products Catalog -->
    <div class="bg-white border border-[#E2E8F0] rounded-2xl shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-[#E2E8F0]">
            <h3 class="text-sm font-bold text-slate-900">Supplied Products ({{ $supplier->products->count() }} SKUs)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider border-b border-[#E2E8F0]">
                        <th class="py-3 px-4 font-semibold">SKU</th>
                        <th class="py-3 px-4 font-semibold">Product Name</th>
                        <th class="py-3 px-4 font-semibold">Category</th>
                        <th class="py-3 px-4 font-semibold text-right">Purchase Price</th>
                        <th class="py-3 px-4 font-semibold text-center">Current Stock</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($supplier->products as $product)
                        <tr>
                            <td class="py-3 px-4 font-mono text-xs text-slate-700">{{ $product->sku }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-900">{{ $product->name }}</td>
                            <td class="py-3 px-4 text-xs text-slate-600">{{ $product->category->name ?? '-' }}</td>
                            <td class="py-3 px-4 text-right text-xs text-slate-700">Rp {{ number_format((float) $product->purchase_price, 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-center text-xs font-bold {{ $product->isLowStock() ? 'text-[#E11D48]' : 'text-[#059669]' }}">
                                {{ $product->stock }} {{ $product->unit }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 px-4 text-center text-sm text-slate-500">
                                No products are currently linked to this supplier.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
