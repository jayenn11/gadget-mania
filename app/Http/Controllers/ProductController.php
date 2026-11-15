<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of products (accessible by Admin, Warehouse, Cashier).
     */
    public function index(Request $request): View
    {
        $query = Product::with(['category', 'supplier']);

        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->input('stock_status') === 'low') {
            $query->lowStock();
        } elseif ($request->input('stock_status') === 'safe') {
            $query->whereColumn('stock', '>', 'min_stock');
        }

        $products = $query->orderBy('name', 'asc')->paginate(10)->withQueryString();
        $categories = Category::orderBy('name', 'asc')->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();

        return view('products.index', compact('products', 'categories', 'suppliers'));
    }

    /**
     * Redirect create route to index page (handled via modal).
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('products.index');
    }

    /**
     * Store a newly created product in storage (Admin & Warehouse).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:200'],
            'brand' => ['required', 'string', 'max:100'],
            'category_id' => ['required', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'in:Unit,Pcs,Box,Set,Pack'],
        ], [
            'sku.required' => 'The SKU code is required.',
            'sku.unique' => 'This SKU code is already registered to another product.',
            'name.required' => 'The product name is required.',
            'brand.required' => 'The product brand is required.',
            'category_id.required' => 'Please select a product category.',
            'purchase_price.required' => 'The purchase price is required.',
            'selling_price.required' => 'The selling price is required.',
            'stock.required' => 'The initial stock quantity is required.',
            'min_stock.required' => 'The minimum stock threshold is required.',
            'unit.required' => 'Please select a valid unit of measure.',
            'unit.in' => 'Selected unit must be one of: Unit, Pcs, Box, Set, Pack.',
        ]);

        Product::create($validated);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product "' . $validated['name'] . '" has been created successfully.');
    }

    /**
     * Redirect edit route to index page (handled via modal).
     */
    public function edit(Product $product): RedirectResponse
    {
        return redirect()->route('products.index');
    }

    /**
     * Update the specified product in storage (Admin & Warehouse).
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product->id)],
            'name' => ['required', 'string', 'max:200'],
            'brand' => ['required', 'string', 'max:100'],
            'category_id' => ['required', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'in:Unit,Pcs,Box,Set,Pack'],
        ], [
            'sku.required' => 'The SKU code is required.',
            'sku.unique' => 'This SKU code is already in use by another product.',
            'name.required' => 'The product name is required.',
            'brand.required' => 'The product brand is required.',
            'category_id.required' => 'Please select a product category.',
            'purchase_price.required' => 'The purchase price is required.',
            'selling_price.required' => 'The selling price is required.',
            'stock.required' => 'The stock quantity is required.',
            'min_stock.required' => 'The minimum stock threshold is required.',
            'unit.required' => 'Please select a valid unit of measure.',
            'unit.in' => 'Selected unit must be one of: Unit, Pcs, Box, Set, Pack.',
        ]);

        $product->update($validated);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product "' . $product->name . '" has been updated successfully.');
    }

    /**
     * Remove the specified product from storage (Admin & Warehouse).
     */
    public function destroy(Product $product): RedirectResponse
    {
        if ($product->stockMovements()->exists() || $product->purchaseOrderItems()->exists()) {
            return redirect()
                ->route('products.index')
                ->with('error', 'Product "' . $product->name . '" cannot be deleted because it has existing stock movement or Purchase Order records.');
        }

        $productName = $product->name;
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Product "' . $productName . '" has been deleted successfully.');
    }
}
