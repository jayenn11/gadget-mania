<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    /**
     * Display the Stock Movement audit log (accessible by all roles).
     */
    public function index(Request $request): View
    {
        $query = StockMovement::with(['product.category', 'user']);

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

        if ($request->filled('type') && in_array($request->string('type')->toString(), ['in', 'out'], true)) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->string('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->string('end_date'));
        }

        $movements = $query
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('stock-movements.index', compact('movements'));
    }
}
