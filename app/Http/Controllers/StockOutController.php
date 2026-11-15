<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockOutController extends Controller
{
    /**
     * Display a listing of Sales / Stock Out transactions (Admin & Cashier).
     */
    public function index(Request $request): View
    {
        $query = StockMovement::with(['product.category', 'user'])
            ->where('type', 'out');

        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $query->where(function ($q) use ($search) {
                $q->where('reference_code', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->string('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->string('end_date'));
        }

        $stockOuts = $query
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $products = Product::orderBy('name', 'asc')->get();

        return view('stock-outs.index', compact('stockOuts', 'products'));
    }

    /**
     * Store a newly recorded Sale / Stock Out transaction (multi-item).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ], [
            'items.required' => 'Please add at least one product item to record a sale.',
            'items.*.product_id.required' => 'Please select a product for each row.',
            'items.*.product_id.distinct' => 'Duplicate products are not allowed in the same transaction.',
            'items.*.quantity.required' => 'Quantity is required for each item.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.unit_price.required' => 'Selling price is required for each item.',
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                // Aggregate requested quantities per product_id in case the same product is added multiple times
                $requestedByProduct = [];
                foreach ($validated['items'] as $idx => $item) {
                    $pid = (int) $item['product_id'];
                    $requestedByProduct[$pid] = ($requestedByProduct[$pid] ?? 0) + (int) $item['quantity'];
                }

                // Lock all involved products and validate stock availability strictly
                $lockedProducts = [];
                foreach ($requestedByProduct as $productId => $totalRequestedQty) {
                    $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();

                    if ($totalRequestedQty > $product->stock) {
                        throw ValidationException::withMessages([
                            'items' => "Insufficient stock for \"{$product->name}\" ({$product->sku}). Available stock: {$product->stock} {$product->unit}, requested: {$totalRequestedQty} {$product->unit}.",
                        ]);
                    }

                    $lockedProducts[$productId] = $product;
                }

                $now = Carbon::now();

                foreach ($validated['items'] as $item) {
                    $product = $lockedProducts[(int) $item['product_id']];
                    $qty = (int) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $totalPrice = round($qty * $unitPrice, 2);

                    // Deduct stock
                    $product->stock -= $qty;
                    $product->save();

                    // Generate unique TRX-OUT-YYYYMMDD-XXXX reference code
                    $referenceCode = $this->generateStockOutReferenceCode();

                    StockMovement::create([
                        'reference_code' => $referenceCode,
                        'product_id' => $product->id,
                        'user_id' => $request->user()->id,
                        'type' => 'out',
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'notes' => $validated['notes'] ?: 'Retail store sale / Stock out',
                        'transaction_date' => $now,
                    ]);
                }
            });
        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('stock-outs.index')
            ->with('success', 'Sale / Stock Out transaction has been recorded and product inventory has been updated.');
    }

    /**
     * Generate a unique TRX-OUT-YYYYMMDD-XXXX code.
     */
    private function generateStockOutReferenceCode(): string
    {
        $datePrefix = 'TRX-OUT-' . Carbon::now()->format('Ymd') . '-';

        $latest = StockMovement::where('reference_code', 'like', $datePrefix . '%')
            ->lockForUpdate()
            ->orderBy('reference_code', 'desc')
            ->first();

        $nextSequence = 1;
        if ($latest && preg_match('/-(\d{4})$/', $latest->reference_code, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        do {
            $candidate = $datePrefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
            $exists = StockMovement::where('reference_code', $candidate)->exists();
            $nextSequence++;
        } while ($exists);

        return $candidate;
    }
}
