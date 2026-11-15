<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Category::withCount('products');

        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $categories = $query->orderBy('name', 'asc')->paginate(10)->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('categories.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'The category name is required.',
            'name.unique' => 'This category name already exists.',
        ]);

        Category::create($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category "' . $validated['name'] . '" has been created successfully.');
    }

    public function edit(Category $category): RedirectResponse
    {
        return redirect()->route('categories.index');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'The category name is required.',
            'name.unique' => 'This category name is already in use.',
        ]);

        $category->update($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category "' . $category->name . '" has been updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return redirect()
                ->route('categories.index')
                ->with('error', 'Category "' . $category->name . '" cannot be deleted because it is still assigned to active products.');
        }

        $categoryName = $category->name;
        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category "' . $categoryName . '" has been deleted successfully.');
    }
}
