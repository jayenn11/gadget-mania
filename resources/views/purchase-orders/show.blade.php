<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-lg font-bold text-slate-900">Purchase Order Details</h1>
                <p class="text-xs text-slate-500">{{ $purchaseOrder->po_number }} &bull; Ordered on {{ $purchaseOrder->order_date?->format('d M Y') }}</p>
            </div>
        </div>
    </x-slot>

    @php
        $normalizedStatus = match($purchaseOrder->status) {
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

    <div x-data="{
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
            }
        }"
        @keydown.escape.window="workflowModal.open = false"
        class="space-y-6">

        <!-- PO Header & Workflow Actions Card -->
        <div class="bg-white border border-[#E2E8F0] rounded-2xl p-6 shadow-xs">
            <div class="flex flex-wrap items-start justify-between gap-4 pb-5 border-b border-slate-100">
                <div>
                    <div class="flex items-center gap-2.5 mb-2">
                        <span class="font-mono text-lg font-bold text-slate-900">{{ $purchaseOrder->po_number }}</span>
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold border {{ $badge['style'] }}">
                            {{ $badge['label'] }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500">
                        Created by <span class="font-semibold text-slate-700">{{ $purchaseOrder->user->name ?? '-' }}</span>
                        on {{ $purchaseOrder->created_at?->format('d M Y, H:i') }}
                    </p>
                </div>

                <!-- Workflow Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('purchase-orders.index') }}"
                       class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        &larr; Back to Purchase Orders
                    </a>

                    @if($normalizedStatus === 'pending')
                        <button type="button"
                                @click="openWorkflowModal({
                                    action: '{{ route('purchase-orders.process', $purchaseOrder) }}',
                                    method: 'PATCH',
                                    title: 'Mark as Processing',
                                    description: 'Confirm that Purchase Order {{ $purchaseOrder->po_number }} is now being processed with the supplier?',
                                    confirmLabel: 'Mark as Processing',
                                    confirmStyle: 'bg-[#2563EB] hover:bg-blue-700',
                                    iconStyle: 'bg-blue-50 border-blue-100 text-[#2563EB]'
                                })"
                                class="px-4 py-2 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition">
                            Mark as Processing
                        </button>
                    @endif

                    @if(in_array($normalizedStatus, ['pending', 'processing'], true))
                        <button type="button"
                                @click="openWorkflowModal({
                                    action: '{{ route('purchase-orders.receive', $purchaseOrder) }}',
                                    method: 'PATCH',
                                    title: 'Receive Goods (Mark as Received)',
                                    description: 'Confirm physical receipt of all items in {{ $purchaseOrder->po_number }}? Product stock quantities will be incremented and Stock In audit logs will be created automatically.',
                                    confirmLabel: 'Receive Goods (Mark as Received)',
                                    confirmStyle: 'bg-emerald-600 hover:bg-emerald-700',
                                    iconStyle: 'bg-emerald-50 border-emerald-100 text-emerald-600'
                                })"
                                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition">
                            Receive Goods (Mark as Received)
                        </button>

                        <button type="button"
                                @click="openWorkflowModal({
                                    action: '{{ route('purchase-orders.cancel', $purchaseOrder) }}',
                                    method: 'PATCH',
                                    title: 'Cancel Purchase Order',
                                    description: 'Are you sure you want to cancel Purchase Order {{ $purchaseOrder->po_number }}? This action cannot be undone.',
                                    confirmLabel: 'Cancel Order',
                                    confirmStyle: 'bg-[#E11D48] hover:bg-rose-700',
                                    iconStyle: 'bg-rose-50 border-rose-100 text-[#E11D48]'
                                })"
                                class="px-4 py-2 rounded-xl border border-rose-200 hover:bg-rose-50 text-[#E11D48] text-xs font-semibold transition">
                            Cancel Order
                        </button>
                    @endif
                </div>
            </div>

            <!-- Supplier & Order Info Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-5 text-sm">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Supplier Information</span>
                    <span class="font-bold text-slate-900 mt-1 block">{{ $purchaseOrder->supplier->name ?? '-' }}</span>
                    <span class="text-xs text-slate-600 block mt-0.5">PIC: {{ $purchaseOrder->supplier->contact_person ?? '-' }} ({{ $purchaseOrder->supplier->phone ?? '-' }})</span>
                    <span class="text-xs text-slate-500 block mt-0.5">{{ $purchaseOrder->supplier->address ?? '' }}</span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Order Summary</span>
                    <span class="font-bold text-slate-900 mt-1 block">Order Date: {{ $purchaseOrder->order_date?->format('d F Y') }}</span>
                    <span class="text-xs text-slate-600 block mt-0.5">Total Line Items: {{ $purchaseOrder->items->count() }} SKUs ({{ $purchaseOrder->items->sum('quantity') }} Units)</span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Order Notes</span>
                    <p class="text-xs text-slate-700 mt-1 leading-relaxed">{{ $purchaseOrder->notes ?: 'No additional notes provided.' }}</p>
                </div>
            </div>
        </div>

        <!-- Ordered Items Table -->
        <div class="bg-white border border-[#E2E8F0] rounded-2xl shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-[#E2E8F0] flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900">Purchase Order Line Items</h2>
                <span class="text-sm font-bold text-[#2563EB]">
                    Grand Total: Rp {{ number_format((float) $purchaseOrder->total_amount, 0, ',', '.') }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider border-b border-[#E2E8F0]">
                            <th class="py-3.5 px-4 font-semibold">#</th>
                            <th class="py-3.5 px-4 font-semibold whitespace-nowrap">SKU</th>
                            <th class="py-3.5 px-4 font-semibold min-w-[240px]">Product Name & Brand</th>
                            <th class="py-3.5 px-4 font-semibold">Category</th>
                            <th class="py-3.5 px-4 font-semibold text-center whitespace-nowrap">Ordered Qty</th>
                            <th class="py-3.5 px-4 font-semibold text-right whitespace-nowrap">Unit Price</th>
                            <th class="py-3.5 px-4 font-semibold text-right whitespace-nowrap">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($purchaseOrder->items as $idx => $item)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 text-xs text-slate-500">{{ $idx + 1 }}</td>
                                <td class="py-3.5 px-4 font-mono text-xs font-semibold text-slate-700 whitespace-nowrap">{{ $item->product->sku ?? '-' }}</td>
                                <td class="py-3.5 px-4 min-w-[240px]">
                                    <div class="font-semibold text-slate-900">{{ $item->product->name ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">Brand: {{ $item->product->brand ?? '-' }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600">{{ $item->product->category->name ?? '-' }}</td>
                                <td class="py-3.5 px-4 text-center font-bold text-slate-900 whitespace-nowrap">
                                    {{ $item->quantity }} {{ $item->product->unit ?? 'Unit' }}
                                </td>
                                <td class="py-3.5 px-4 text-right text-xs text-slate-700 whitespace-nowrap">
                                    Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-semibold text-slate-900 whitespace-nowrap">
                                    Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50/90 border-t border-[#E2E8F0]">
                            <td colspan="6" class="py-3.5 px-4 text-right text-xs font-bold uppercase tracking-wider text-slate-600">
                                Total Purchase Order Amount
                            </td>
                            <td class="py-3.5 px-4 text-right text-base font-bold text-[#2563EB] whitespace-nowrap">
                                Rp {{ number_format((float) $purchaseOrder->total_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Custom Confirmation Modal -->
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
