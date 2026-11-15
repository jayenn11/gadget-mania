<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Gadget Mania Inventory') }} - Sign In</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased bg-[#F8FAFC]">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-10">
            <!-- Brand Header -->
            <div class="text-center mb-6">
                <a href="/" class="inline-flex items-center gap-3 group">
                    <div class="w-12 h-12 rounded-2xl bg-[#2563EB] flex items-center justify-center text-white shadow-md shadow-blue-600/20">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="text-left">
                        <span class="block text-xl font-bold tracking-tight text-slate-900">Gadget Mania</span>
                        <span class="block text-xs font-medium text-slate-500">Enterprise Electronics Inventory System</span>
                    </div>
                </a>
            </div>

            <div class="w-full sm:max-w-md bg-white border border-[#E2E8F0] shadow-sm rounded-2xl p-6 sm:p-8">
                {{ $slot }}
            </div>

            <p class="mt-6 text-xs text-slate-400">
                &copy; {{ date('Y') }} Gadget Mania Inventory System. All rights reserved.
            </p>
        </div>
    </body>
</html>
