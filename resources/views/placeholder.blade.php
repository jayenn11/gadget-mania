<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Operations & Analytics Module</h1>
            <p class="text-xs text-slate-500">Upcoming transactional module for Stage 4 implementation</p>
        </div>
    </x-slot>

    <div class="bg-white border border-[#E2E8F0] rounded-2xl p-8 text-center max-w-xl mx-auto my-8 shadow-xs">
        <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-blue-50 text-[#2563EB] flex items-center justify-center">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </div>
        <h2 class="text-lg font-bold text-slate-900 mb-2">
            {{ ucwords(str_replace('-', ' ', request('modul', 'Operations'))) }} Module
        </h2>
        <p class="text-sm text-slate-600 mb-6">
            This module is scheduled for Stage 4 (Purchase Orders, Stock In, Retail Sales / Stock Out, and Trend Analytics).
        </p>
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#2563EB] hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition">
            &larr; Back to Dashboard
        </a>
    </div>
</x-app-layout>
