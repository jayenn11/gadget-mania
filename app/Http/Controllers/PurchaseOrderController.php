<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of Purchase Orders (Admin & Warehouse).
     */
    public function index(Request $request): View
    {
        $query = PurchaseOrder::with(['supplier', 'user', 'items.product']);

        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            $statusMap = match ($status) {
                'processing' => ['processing', 'diproses'],
                'received' => ['received', 'diterima'],
                'cancelled' => ['cancelled', 'dibatalkan'],
                default => [$status],
            };
            $query->whereIn('status', $statusMap);
        }

        $purchaseOrders = $query
            ->orderBy('order_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $products = Product::with('category')->orderBy('name', 'asc')->get();

        return view('purchase-orders.index', compact('purchaseOrders', 'suppliers', 'products'));
    }

    /**
     * Redirect create route to index with modal trigger.
     */
    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('purchase-orders.index', array_merge(
            ['create' => 1],
            $request->only(['product_id', 'supplier_id'])
        ));
    }

    /**
     * Store a newly created Purchase Order in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ], [
            'supplier_id.required' => 'Please select a supplier.',
            'order_date.required' => 'The order date is required.',
            'items.required' => 'Please add at least one product item to the Purchase Order.',
            'items.*.product_id.required' => 'Please select a product for each item row.',
            'items.*.product_id.distinct' => 'Duplicate products are not allowed in the same Purchase Order.',
            'items.*.quantity.required' => 'Quantity is required for each item.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.unit_price.required' => 'Unit price is required for each item.',
        ]);

        $po = DB::transaction(function () use ($validated, $request) {
            $poNumber = $this->generatePoNumber();

            $totalAmount = 0;
            $itemsPayload = [];

            foreach ($validated['items'] as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['unit_price'];
                $subtotal = round($qty * $price, 2);
                $totalAmount += $subtotal;

                $itemsPayload[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $subtotal,
                ];
            }

            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $validated['supplier_id'],
                'user_id' => $request->user()->id,
                'order_date' => $validated['order_date'],
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($itemsPayload as $payload) {
                $purchaseOrder->items()->create($payload);
            }

            return $purchaseOrder;
        });

        return redirect()
            ->route('purchase-orders.index')
            ->with('success', 'Purchase Order "' . $po->po_number . '" has been created with status Pending.');
    }

    /**
     * Display the specified Purchase Order details.
     */
    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'user', 'items.product.category']);

        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    /**
     * Transition Purchase Order from pending to processing.
     */
    public function markAsProcessing(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $result = DB::transaction(function () use ($purchaseOrder) {
            $po = PurchaseOrder::whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();

            if ($po->status !== 'pending') {
                return false;
            }

            $po->update(['status' => 'processing']);

            return true;
        });

        if (! $result) {
            return redirect()
                ->back()
                ->with('error', 'Only pending Purchase Orders can be marked as Processing.');
        }

        return redirect()
            ->back()
            ->with('success', 'Purchase Order "' . $purchaseOrder->po_number . '" is now marked as Processing.');
    }

    /**
     * Cancel a pending or processing Purchase Order.
     */
    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $result = DB::transaction(function () use ($purchaseOrder) {
            $po = PurchaseOrder::whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();

            if (in_array($po->status, ['received', 'diterima', 'cancelled', 'dibatalkan'], true)) {
                return false;
            }

            $po->update(['status' => 'cancelled']);

            return true;
        });

        if (! $result) {
            return redirect()
                ->back()
                ->with('error', 'Purchase Order "' . $purchaseOrder->po_number . '" cannot be cancelled because it is already received or cancelled.');
        }

        return redirect()
            ->back()
            ->with('success', 'Purchase Order "' . $purchaseOrder->po_number . '" has been cancelled.');
    }

    /**
     * Receive goods from a Purchase Order, increment product stock, and log Stock Movements.
     */
    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $result = DB::transaction(function () use ($request, $purchaseOrder) {
            $po = PurchaseOrder::with('items')
                ->whereKey($purchaseOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($po->status, ['received', 'diterima', 'cancelled', 'dibatalkan'], true)) {
                return false;
            }

            $po->update(['status' => 'received']);

            $totalItems = $po->items->count();
            $now = Carbon::now();

            foreach ($po->items as $index => $item) {
                $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();

                $product->stock += $item->quantity;
                if ((float) $product->purchase_price !== (float) $item->unit_price) {
                    $product->purchase_price = $item->unit_price;
                }
                $product->save();

                $referenceCode = $this->resolvePoReferenceCode($po->po_number, $index + 1, $totalItems);

                StockMovement::create([
                    'reference_code' => $referenceCode,
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->subtotal,
                    'notes' => 'Goods received from PO #' . $po->po_number,
                    'transaction_date' => $now,
                ]);
            }

            return true;
        });

        if (! $result) {
            return redirect()
                ->back()
                ->with('error', 'Purchase Order "' . $purchaseOrder->po_number . '" has already been processed or cancelled.');
        }

        return redirect()
            ->back()
            ->with('success', 'Goods for Purchase Order "' . $purchaseOrder->po_number . '" have been received and inventory stock has been updated.');
    }

    /**
     * Generate a unique PO-YYYYMMDD-XXXX code.
     */
    private function generatePoNumber(): string
    {
        $datePrefix = 'PO-' . Carbon::now()->format('Ymd') . '-';

        $latestPo = PurchaseOrder::where('po_number', 'like', $datePrefix . '%')
            ->lockForUpdate()
            ->orderBy('po_number', 'desc')
            ->first();

        $nextSequence = 1;
        if ($latestPo && preg_match('/-(\d{4})$/', $latestPo->po_number, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        do {
            $candidate = $datePrefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
            $exists = PurchaseOrder::where('po_number', $candidate)->exists();
            $nextSequence++;
        } while ($exists);

        return $candidate;
    }

    /**
     * Generate a unique reference code for stock_movements tied to the PO number.
     */
    private function resolvePoReferenceCode(string $poNumber, int $itemNumber, int $totalItems): string
    {
        if ($totalItems === 1 && ! StockMovement::where('reference_code', $poNumber)->exists()) {
            return $poNumber;
        }

        $candidate = $poNumber . '-' . str_pad((string) $itemNumber, 2, '0', STR_PAD_LEFT);
        $suffix = $itemNumber;

        while (StockMovement::where('reference_code', $candidate)->exists()) {
            $suffix++;
            $candidate = $poNumber . '-' . str_pad((string) $suffix, 2, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }
}
