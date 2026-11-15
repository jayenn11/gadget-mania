<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Categories</h1>
            <p class="text-xs text-slate-500">Manage electronics product classifications at Gadget Mania</p>
        </div>
    </x-slot>

    @php
        $initialModalOpen = $errors->any();
        $initialModalMode = old('_modal_mode', 'create');
        $initialForm = [
            'id' => old('_edit_id', ''),
            'name' => old('name', ''),
            'description' => old('description', ''),
        ];
    @endphp

    <div x-data="{
            search: @js(request('search', '')),
            isLoading: false,
            modalOpen: @js($initialModalOpen),
            modalMode: @js($initialModalMode),
            baseUrl: @js(route('categories.index')),
            form: @js($initialForm),
            openCreateModal() {
                this.modalMode = 'create';
                this.form = { id: '', name: '', description: '' };
                this.modalOpen = true;
            },
            openEditModal(category) {
                this.modalMode = 'edit';
                this.form = {
                    id: category.id,
                    name: category.name,
                    description: category.description || ''
                };
                this.modalOpen = true;
            },
            get formAction() {
                return this.modalMode === 'edit' && this.form.id
                    ? `${this.baseUrl}/${this.form.id}`
                    : this.baseUrl;
            },
            async applyFilter(pageUrl = null) {
                this.isLoading = true;
                try {
                    const url = new URL(pageUrl || this.baseUrl, window.location.origin);
                    if (!pageUrl) {
                        if (this.search) url.searchParams.set('search', this.search);
                        else url.searchParams.delete('search');
                    }
                    const response = await fetch(url.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newContainer = doc.getElementById('table-container');
                    if (newContainer) {
                        document.getElementById('table-container').innerHTML = newContainer.innerHTML;
                        window.history.replaceState({}, '', url.toString());
                    }
                } finally {
                    this.isLoading = false;
                }
            },
            resetFilters() {
                this.search = '';
                this.applyFilter();
            }
        }"
        @keydown.escape.window="modalOpen = false"
        class="space-y-6">

        <!-- Action & Live Search Bar -->
        <div class="bg-white border border-[#E2E8F0] rounded-2xl p-4 sm:p-5 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex-1 flex items-center gap-2 max-w-md">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text"
                               x-model="search"
                               x-on:input.debounce.350ms="applyFilter()"
                               placeholder="Search category name or description..."
                               class="w-full rounded-xl border-[#E2E8F0] text-sm pl-9 pr-3.5 py-2 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>
                    <button type="button"
                            x-show="search !== ''"
                            x-cloak
                            @click="resetFilters()"
                            class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                        Reset
                    </button>
                </div>

                <button type="button"
                        @click="openCreateModal()"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add Category</span>
                </button>
            </div>
        </div>

        <!-- Categories Table Container -->
        <div id="table-container"
             @click="
                const link = $event.target.closest('nav[role=navigation] a');
                if (link) {
                    $event.preventDefault();
                    applyFilter(link.href);
                }
             "
             :class="{ 'opacity-60 pointer-events-none': isLoading }"
             class="bg-white border border-[#E2E8F0] rounded-2xl shadow-xs overflow-hidden transition-opacity duration-150">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider border-b border-[#E2E8F0]">
                            <th class="py-3.5 px-4 font-semibold w-14">#</th>
                            <th class="py-3.5 px-4 font-semibold">Category Name</th>
                            <th class="py-3.5 px-4 font-semibold">Description</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Products Count</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($categories as $index => $category)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 text-xs text-slate-500">
                                    {{ $categories->firstItem() + $index }}
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    {{ $category->name }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600 max-w-md">
                                    {{ $category->description ?: '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-[#2563EB]">
                                        {{ $category->products_count }} SKUs
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button"
                                                @click="openEditModal(@js($category->only(['id', 'name', 'description'])))"
                                                class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-xs font-semibold text-slate-700 transition">
                                            Edit
                                        </button>
                                        <button type="button"
                                                @click="$dispatch('open-delete-modal', {
                                                    action: '{{ route('categories.destroy', $category) }}',
                                                    title: 'Delete Category',
                                                    itemName: @js($category->name)
                                                })"
                                                class="px-2.5 py-1.5 rounded-lg border border-rose-200 hover:bg-rose-50 text-xs font-semibold text-[#E11D48] transition">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 px-4 text-center text-sm text-slate-500">
                                    No product categories found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($categories->hasPages())
                <div class="px-6 py-4 border-t border-[#E2E8F0] bg-slate-50/50">
                    {{ $categories->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>

        <!-- Create / Edit Category Modal -->
        <div x-show="modalOpen" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="modalOpen"
                     x-transition.opacity
                     @click="modalOpen = false"
                     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>

                <div x-show="modalOpen"
                     x-transition
                     class="relative w-full max-w-lg bg-white border border-[#E2E8F0] rounded-2xl shadow-xl p-6 sm:p-8 z-10 my-8 overflow-visible">
                <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900" x-text="modalMode === 'edit' ? 'Edit Category' : 'Add New Category'"></h2>
                        <p class="text-xs text-slate-500 mt-0.5" x-text="modalMode === 'edit' ? 'Update category name or description.' : 'Create a new classification for electronics products.'"></p>
                    </div>
                    <button type="button" @click="modalOpen = false"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form method="POST" :action="formAction" class="space-y-4 mt-5">
                    @csrf
                    <template x-if="modalMode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <input type="hidden" name="_modal_mode" x-model="modalMode">
                    <input type="hidden" name="_edit_id" x-model="form.id">

                    <div>
                        <label for="modal_cat_name" class="block text-sm font-semibold text-slate-700 mb-1">
                            Category Name <span class="text-[#E11D48]">*</span>
                        </label>
                        <input type="text" id="modal_cat_name" name="name" x-model="form.name" required
                               placeholder="e.g. Wearables & Smartwatches"
                               class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div>
                        <label for="modal_cat_desc" class="block text-sm font-semibold text-slate-700 mb-1">
                            Description <span class="text-xs font-normal text-slate-400">(Optional)</span>
                        </label>
                        <textarea id="modal_cat_desc" name="description" x-model="form.description" rows="3"
                                  placeholder="Enter a brief description of this category..."
                                  class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <div class="pt-4 border-t border-[#E2E8F0] flex items-center justify-end gap-3">
                        <button type="button" @click="modalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition"
                                x-text="modalMode === 'edit' ? 'Save Changes' : 'Create Category'">
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
