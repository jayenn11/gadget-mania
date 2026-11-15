<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name', 'Gadget Mania Inventory') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @media print {
            @page {
                margin: 0;
            }
        }
    </style>
</head>
<body class="font-sans antialiased bg-[#F8FAFC] text-slate-800"
      x-data="{
          sidebarOpen: false,
          deleteModal: {
              open: false,
              action: '',
              title: 'Confirm Deletion',
              itemName: ''
          },
          openDeleteModal(detail) {
              this.deleteModal.action = detail.action || '';
              this.deleteModal.title = detail.title || 'Confirm Deletion';
              this.deleteModal.itemName = detail.itemName || 'this item';
              this.deleteModal.open = true;
          }
      }"
      @open-delete-modal.window="openDeleteModal($event.detail)"
      @keydown.escape.window="deleteModal.open = false">
    @php
        $currentUser = auth()->user();
        $lowStockProducts = \App\Models\Product::with('category')->lowStock()->orderBy('stock', 'asc')->get();
        $lowStockCount = $lowStockProducts->count();

        $roleBadge = match($currentUser->role) {
            'admin' => ['label' => 'Administrator', 'bg' => 'bg-blue-500/20 text-blue-300 border-blue-400/30'],
            'gudang' => ['label' => 'Warehouse', 'bg' => 'bg-emerald-500/20 text-emerald-300 border-emerald-400/30'],
            default => ['label' => 'Cashier', 'bg' => 'bg-amber-500/20 text-amber-300 border-amber-400/30'],
        };
    @endphp

    <div class="min-h-screen flex">
        <!-- Mobile Sidebar Backdrop -->
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
             x-transition.opacity
             class="no-print fixed inset-0 z-30 bg-slate-900/60 backdrop-blur-xs lg:hidden"></div>

        <!-- Sidebar Navigation -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="no-print fixed inset-y-0 left-0 z-40 w-68 bg-slate-900 text-slate-200 flex flex-col transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-0 shrink-0 border-r border-slate-800">

            <!-- Brand Logo (Uniform p-5 padding) -->
            <div class="p-5 flex items-center justify-between border-b border-slate-800/80">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#2563EB] flex items-center justify-center text-white shadow-sm shadow-blue-500/30 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-base font-bold tracking-tight text-white leading-none">Gadget Mania</span>
                        <span class="block text-[11px] text-slate-400 mt-1">Enterprise Inventory</span>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Simplified User Profile & Role Badge Card -->
            <div class="px-4 py-4 border-b border-slate-800/80">
                <div class="rounded-xl bg-slate-800/70 border border-slate-700/60 p-3.5">
                    <p class="text-sm text-white font-semibold truncate">{{ $currentUser->name }}</p>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $roleBadge['bg'] }}">
                            {{ $roleBadge['label'] }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto text-sm">
                <div class="px-3 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">Overview</div>

                <!-- Dashboard (All Roles) -->
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('dashboard') ? 'bg-[#2563EB] text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Dashboard</span>
                </a>

                <div class="pt-4 px-3 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">Master Data</div>

                <!-- Products (All Roles) -->
                <a href="{{ route('products.index') }}"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('products.*') ? 'bg-[#2563EB] text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>Products</span>
                    </div>
                    @if($lowStockCount > 0)
                        <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-rose-500/20 text-rose-300 border border-rose-400/30">
                            {{ $lowStockCount }}
                        </span>
                    @endif
                </a>

                <!-- Categories & Suppliers (Admin & Warehouse) -->
                @if($currentUser->isAdmin() || $currentUser->isGudang())
                    <a href="{{ route('categories.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('categories.*') ? 'bg-[#2563EB] text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <span>Categories</span>
                    </a>

                    <a href="{{ route('suppliers.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('suppliers.*') ? 'bg-[#2563EB] text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>Suppliers</span>
                    </a>
                @endif

                <div class="pt-4 px-3 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">Operations & Reports</div>

                <!-- Purchase Orders (Admin & Warehouse) -->
                @if($currentUser->isAdmin() || $currentUser->isGudang())
                    <a href="{{ route('purchase-orders.index') }}"
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('purchase-orders.*') ? 'bg-[#2563EB] text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Purchase Orders</span>
                        </div>
                    </a>
                @endif

                <!-- Sales / Stock Out (Admin & Cashier) -->
                @if($currentUser->isAdmin() || $currentUser->isKasir())
                    <a href="{{ route('stock-outs.index') }}"
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('stock-outs.*') ? 'bg-[#2563EB] text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span>Sales / Stock Out</span>
                        </div>
                    </a>
                @endif

                <!-- Stock Movements / Audit Log (All Roles) -->
                <a href="{{ route('stock-movements.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('stock-movements.*') ? 'bg-[#2563EB] text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                    <span>Stock Movements</span>
                </a>

                <!-- Reports & Analytics (All Roles) -->
                <a href="{{ route('reports.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('reports.*') ? 'bg-[#2563EB] text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>Reports & Analytics</span>
                </a>

                <!-- User Management (Admin Only) -->
                @if($currentUser->isAdmin())
                    <div class="pt-4 px-3 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">Administration</div>
                    <a href="{{ route('users.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('users.*') ? 'bg-[#2563EB] text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>User Management</span>
                    </a>
                @endif
            </nav>

            <!-- Sidebar Footer -->
            <div class="p-4 border-t border-slate-800/80 text-xs text-slate-400">
                <div class="flex items-center justify-between">
                    <span>System Status</span>
                    <span class="inline-flex items-center gap-1.5 text-emerald-400 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Operational
                    </span>
                </div>
            </div>
        </aside>

        <!-- Main Content Column -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar -->
            <header class="no-print h-16 bg-white border-b border-[#E2E8F0] px-4 sm:px-6 lg:px-8 flex items-center justify-between sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        @isset($header)
                            {{ $header }}
                        @else
                            <h1 class="text-lg font-bold text-slate-900">Inventory Dashboard</h1>
                        @endisset
                    </div>
                </div>

                <!-- Right Actions: Low Stock Notification Bell + User Profile Dropdown -->
                <div class="flex items-center gap-3">
                    <!-- Notification Bell Dropdown -->
                    <div class="relative" x-data="{ notifOpen: false }">
                        <button @click="notifOpen = !notifOpen"
                                class="relative p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition"
                                title="Low Stock Alerts">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if($lowStockCount > 0)
                                <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-[#E11D48] text-white text-[10px] font-bold flex items-center justify-center">
                                    {{ $lowStockCount }}
                                </span>
                            @endif
                        </button>

                        <!-- Notification Popover -->
                        <div x-show="notifOpen" x-cloak @click.outside="notifOpen = false"
                             x-transition
                             class="absolute right-0 mt-2 w-80 sm:w-96 bg-white border border-[#E2E8F0] rounded-2xl shadow-lg overflow-hidden z-50">
                            <div class="px-4 py-3 bg-slate-50 border-b border-[#E2E8F0] flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Low Stock Alert</span>
                                <span class="px-2 py-0.5 text-xs font-bold rounded-full {{ $lowStockCount > 0 ? 'bg-rose-100 text-[#E11D48]' : 'bg-emerald-100 text-[#059669]' }}">
                                    {{ $lowStockCount }} Items
                                </span>
                            </div>
                            <div class="max-h-72 overflow-y-auto divide-y divide-slate-100">
                                @forelse($lowStockProducts as $lowItem)
                                    <a href="{{ route('products.index', ['stock_status' => 'low']) }}" class="block px-4 py-3 hover:bg-slate-50 transition">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-semibold text-slate-900">{{ $lowItem->name }}</span>
                                            <span class="text-xs font-bold text-[#E11D48]">Left: {{ $lowItem->stock }} {{ $lowItem->unit }}</span>
                                        </div>
                                        <div class="flex items-center justify-between mt-1 text-[11px] text-slate-500">
                                            <span>SKU: {{ $lowItem->sku }}</span>
                                            <span>Min. Stock: {{ $lowItem->min_stock }}</span>
                                        </div>
                                    </a>
                                @empty
                                    <div class="px-4 py-6 text-center text-xs text-slate-500">
                                        All products are well above minimum stock levels.
                                    </div>
                                @endforelse
                            </div>
                            <div class="px-4 py-2.5 bg-slate-50 border-t border-[#E2E8F0] text-center">
                                <a href="{{ route('products.index', ['stock_status' => 'low']) }}" class="text-xs font-semibold text-[#2563EB] hover:underline">
                                    View All Low Stock Products &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Dropdown -->
                    <div class="relative" x-data="{ userOpen: false }">
                        <button @click="userOpen = !userOpen"
                                class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-xl border border-[#E2E8F0] hover:bg-slate-50 transition">
                            <div class="w-8 h-8 rounded-lg bg-blue-600/10 text-[#2563EB] font-bold text-xs flex items-center justify-center uppercase">
                                {{ substr($currentUser->name, 0, 2) }}
                            </div>
                            <div class="hidden sm:block text-left">
                                <span class="block text-xs font-semibold text-slate-800 leading-tight">{{ $currentUser->name }}</span>
                                <span class="block text-[10px] font-medium uppercase text-slate-500">{{ $roleBadge['label'] }}</span>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="userOpen" x-cloak @click.outside="userOpen = false"
                             x-transition
                             class="absolute right-0 mt-2 w-56 bg-white border border-[#E2E8F0] rounded-2xl shadow-lg py-1.5 z-50">
                            <div class="px-4 py-2.5 border-b border-slate-100">
                                <p class="text-xs font-semibold text-slate-900 truncate">{{ $currentUser->name }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ $currentUser->email }}</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Profile Settings
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-[#E11D48] hover:bg-rose-50 text-left">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Workspace -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 print:p-0 print:max-w-none max-w-7xl w-full mx-auto space-y-6">
                <!-- Flash Notifications -->
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                         class="no-print flex items-center justify-between gap-3 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-[#059669] text-sm font-medium shadow-xs">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button @click="show = false" class="text-emerald-600 hover:text-emerald-800">&times;</button>
                    </div>
                @endif

                @if(session('error'))
                    <div x-data="{ show: true }" x-show="show"
                         class="no-print flex items-center justify-between gap-3 px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-[#E11D48] text-sm font-medium shadow-xs">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button @click="show = false" class="text-rose-600 hover:text-rose-800">&times;</button>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Global Reusable Delete Confirmation Modal -->
    <div x-show="deleteModal.open" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <!-- Dark Transparent Backdrop -->
            <div x-show="deleteModal.open"
                 x-transition.opacity
                 @click="deleteModal.open = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>

            <!-- Modal Box -->
            <div x-show="deleteModal.open"
                 x-transition
                 class="relative w-full max-w-md bg-white border border-[#E2E8F0] rounded-2xl shadow-xl p-6 z-10 overflow-visible">
            <div class="flex items-start justify-between">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-100 text-[#E11D48] flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <button type="button" @click="deleteModal.open = false"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-4">
                <h3 class="text-lg font-bold text-slate-900" x-text="deleteModal.title"></h3>
                <p class="mt-1.5 text-sm text-slate-600 leading-relaxed">
                    Are you sure you want to permanently delete
                    <span class="font-bold text-slate-900" x-text="deleteModal.itemName"></span>?
                    This action cannot be undone.
                </p>
            </div>

            <form method="POST" :action="deleteModal.action" class="mt-6 flex items-center justify-end gap-3">
                @csrf
                @method('DELETE')
                <button type="button" @click="deleteModal.open = false"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2.5 rounded-xl bg-[#E11D48] hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition">
                    Yes, Delete
                </button>
            </form>
            </div>
        </div>
    </div>
</body>
</html>
