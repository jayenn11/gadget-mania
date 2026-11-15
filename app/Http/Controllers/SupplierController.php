<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $query = Supplier::withCount(['products', 'purchaseOrders']);

        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('code', 'asc')->paginate(10)->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('suppliers.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:suppliers,code'],
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^[0-9+]{8,20}$/'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['required', 'string'],
        ], [
            'code.required' => 'The supplier code is required.',
            'code.unique' => 'This supplier code is already registered.',
            'name.required' => 'The company name is required.',
            'contact_person.required' => 'The contact person (PIC) name is required.',
            'phone.required' => 'The phone number is required.',
            'phone.regex' => 'The phone number must contain only digits or + (8 to 20 characters).',
            'email.email' => 'Please enter a valid email address.',
            'address.required' => 'The complete supplier address is required.',
        ]);

        Supplier::create($validated);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier "' . $validated['name'] . '" has been added successfully.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load([
            'products.category',
            'purchaseOrders' => fn ($q) => $q->with(['user', 'items.product'])->orderBy('order_date', 'desc'),
        ]);

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): RedirectResponse
    {
        return redirect()->route('suppliers.index');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('suppliers', 'code')->ignore($supplier->id)],
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^[0-9+]{8,20}$/'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['required', 'string'],
        ], [
            'code.required' => 'The supplier code is required.',
            'code.unique' => 'This supplier code is already in use.',
            'name.required' => 'The company name is required.',
            'contact_person.required' => 'The contact person (PIC) name is required.',
            'phone.required' => 'The phone number is required.',
            'phone.regex' => 'The phone number must contain only digits or + (8 to 20 characters).',
            'email.email' => 'Please enter a valid email address.',
            'address.required' => 'The complete supplier address is required.',
        ]);

        $supplier->update($validated);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier "' . $supplier->name . '" has been updated successfully.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchaseOrders()->exists()) {
            return redirect()
                ->route('suppliers.index')
                ->with('error', 'Supplier "' . $supplier->name . '" cannot be deleted because it has existing Purchase Order history.');
        }

        $supplierName = $supplier->name;
        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier "' . $supplierName . '" has been deleted successfully.');
    }
}
