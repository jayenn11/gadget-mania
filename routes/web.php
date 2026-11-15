<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Main Dashboard (All Roles)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Reports & Sales Trend Analytics (All Roles, RBAC-scoped per tab/export)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');

    // Placeholder for legacy references
    Route::view('/modul-operasional', 'placeholder')->name('Operasional.placeholder');

    // Products Catalog View (All Roles)
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');

    // Stock Movement History / Audit Log (All Roles)
    Route::get('/stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');

    // Master Products (CRUD), Categories, Suppliers, and Purchase Orders (Admin & Warehouse)
    Route::middleware('role:admin,gudang')->group(function () {
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('suppliers', SupplierController::class);

        // Purchase Orders & Goods Receipt Workflow
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('/purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::patch('/purchase-orders/{purchase_order}/process', [PurchaseOrderController::class, 'markAsProcessing'])->name('purchase-orders.process');
        Route::patch('/purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
        Route::patch('/purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    });

    // Sales / Stock Out Module (Admin & Cashier)
    Route::middleware('role:admin,kasir')->group(function () {
        Route::get('/stock-outs', [StockOutController::class, 'index'])->name('stock-outs.index');
        Route::post('/stock-outs', [StockOutController::class, 'store'])->name('stock-outs.store');
    });

    // User Account Management (Admin Only)
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
