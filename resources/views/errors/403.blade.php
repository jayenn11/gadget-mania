<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Access Denied | Gadget Mania Inventory</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-[#F8FAFC] text-slate-800 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white border border-[#E2E8F0] rounded-2xl shadow-sm p-8 text-center">
        <div class="w-16 h-16 mx-auto mb-5 rounded-full bg-rose-50 border border-rose-100 flex items-center justify-center text-[#E11D48]">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold tracking-wide uppercase bg-rose-100 text-[#E11D48] mb-3">
            Error 403 &bull; Restricted Access
        </span>

        <h1 class="text-2xl font-bold text-slate-900 mb-2">Access Denied</h1>
        <p class="text-sm text-slate-600 mb-6 leading-relaxed">
            {{ $exception->getMessage() ?: 'You do not have sufficient permissions to view this page under your current role.' }}
        </p>

        @auth
            @php
                $roleLabel = match(auth()->user()->role) {
                    'admin' => 'Administrator',
                    'gudang' => 'Warehouse',
                    default => 'Cashier',
                };
            @endphp
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 mb-6 text-xs text-slate-600">
                Signed in as: <span class="font-semibold text-slate-800">{{ auth()->user()->name }}</span>
                (<span class="uppercase font-bold text-blue-600">{{ $roleLabel }}</span>)
            </div>
        @endauth

        <div class="flex items-center justify-center gap-3">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-[#2563EB] hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
