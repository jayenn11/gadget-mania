<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        $users = $query->orderBy('role', 'asc')->orderBy('name', 'asc')->paginate(10)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('users.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,gudang,kasir'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'The user full name is required.',
            'email.required' => 'The email address is required.',
            'email.unique' => 'This email address is already registered.',
            'role.required' => 'Please select a valid user role.',
            'password.required' => 'A password is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User account "' . $validated['name'] . '" has been created successfully.');
    }

    public function edit(User $user): RedirectResponse
    {
        return redirect()->route('users.index');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:admin,gudang,kasir'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'The user full name is required.',
            'email.required' => 'The email address is required.',
            'email.unique' => 'This email address is already in use by another account.',
            'role.required' => 'Please select a valid user role.',
            'password.min' => 'The new password must be at least 8 characters.',
            'password.confirmed' => 'The new password confirmation does not match.',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'User account "' . $user->name . '" has been updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot delete your own currently active account.');
        }

        if ($user->purchaseOrders()->exists() || $user->stockMovements()->exists()) {
            return redirect()
                ->route('users.index')
                ->with('error', 'User account "' . $user->name . '" cannot be deleted because it has recorded inventory movements or Purchase Orders.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User account "' . $userName . '" has been deleted successfully.');
    }
}
