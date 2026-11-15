<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Tambah Kategori Produk</h1>
            <p class="text-xs text-slate-500">Buat pengelompokan baru untuk katalog produk elektronik</p>
        </div>
    </x-slot>

    <div class="max-w-xl bg-white border border-[#E2E8F0] rounded-2xl shadow-xs p-6 sm:p-8">
        <form method="POST" action="{{ route('categories.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">
                    Nama Kategori <span class="text-[#E11D48]">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       placeholder="Contoh: Wearable & Smartwatch"
                       class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
            </div>

            <div>
                <label for="description" class="block text-sm font-semibold text-slate-700 mb-1">
                    Deskripsi Kategori <span class="text-xs font-normal text-slate-400">(Opsional)</span>
                </label>
                <textarea id="description" name="description" rows="3"
                          placeholder="Tuliskan deskripsi singkat kategori produk ini..."
                          class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]">{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-1.5" />
            </div>

            <div class="pt-4 border-t border-[#E2E8F0] flex items-center justify-end gap-3">
                <a href="{{ route('categories.index') }}"
                   class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Batal
                </a>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white text-sm font-semibold shadow-xs transition">
                    Simpan Kategori
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
