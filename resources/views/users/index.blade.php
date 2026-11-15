<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-slate-900">User Management</h1>
            <p class="text-xs text-slate-500">Manage staff accounts and Role-Based Access Control (Administrator, Warehouse, Cashier)</p>
        </div>
    </x-slot>

    @php
        $initialModalOpen = $errors->any();
        $initialModalMode = old('_modal_mode', 'create');
        $initialForm = [
            'id' => old('_edit_id', ''),
            'name' => old('name', ''),
            'email' => old('email', ''),
            'role' => old('role', 'kasir'),
            'password' => '',
            'password_confirmation' => '',
        ];

        $filterRoleOptions = [
            ['value' => '', 'label' => 'All Roles'],
            ['value' => 'admin', 'label' => 'Administrator'],
            ['value' => 'gudang', 'label' => 'Warehouse'],
            ['value' => 'kasir', 'label' => 'Cashier'],
        ];

        $modalRoleOptions = [
            ['value' => 'kasir', 'label' => 'Cashier (Retail Sales & Catalog View)', 'short' => 'Cashier'],
            ['value' => 'gudang', 'label' => 'Warehouse (Products, Categories, Suppliers, PO & Stock In)', 'short' => 'Warehouse'],
            ['value' => 'admin', 'label' => 'Administrator (Full System Access)', 'short' => 'Administrator'],
        ];
    @endphp

    <div x-data="{
            search: @js(request('search', '')),
            role: @js((string) request('role', '')),
            isLoading: false,
            modalOpen: @js($initialModalOpen),
            modalMode: @js($initialModalMode),
            baseUrl: @js(route('users.index')),
            filterRoleOptions: @js($filterRoleOptions),
            modalRoleOptions: @js($modalRoleOptions),
            form: @js($initialForm),
            openCreateModal() {
                this.modalMode = 'create';
                this.form = {
                    id: '',
                    name: '',
                    email: '',
                    role: 'kasir',
                    password: '',
                    password_confirmation: ''
                };
                this.modalOpen = true;
            },
            openEditModal(user) {
                this.modalMode = 'edit';
                this.form = {
                    id: user.id,
                    name: user.name,
                    email: user.email,
                    role: user.role,
                    password: '',
                    password_confirmation: ''
                };
                this.modalOpen = true;
            },
            findFilterRoleLabel(val) {
                const found = this.filterRoleOptions.find(o => o.value === String(val || ''));
                return found ? found.label : 'All Roles';
            },
            filterRolesForBar(q = '') {
                const query = (q || '').toLowerCase().trim();
                if (!query) return this.filterRoleOptions;
                return this.filterRoleOptions.filter(o => o.label.toLowerCase().includes(query));
            },
            findModalRoleLabel(val) {
                const found = this.modalRoleOptions.find(o => o.value === String(val || ''));
                return found ? found.label : '-- Select Role --';
            },
            filterRolesForModal(q = '') {
                const query = (q || '').toLowerCase().trim();
                if (!query) return this.modalRoleOptions;
                return this.modalRoleOptions.filter(o =>
                    o.label.toLowerCase().includes(query) || o.short.toLowerCase().includes(query)
                );
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

                        if (this.role) url.searchParams.set('role', this.role);
                        else url.searchParams.delete('role');
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
                this.role = '';
                this.applyFilter();
            }
        }"
        @keydown.escape.window="modalOpen = false"
        class="space-y-6">

        <!-- Action & Live Filter Bar -->
        <div class="bg-white border border-[#E2E8F0] rounded-2xl p-4 sm:p-5 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex-1 flex flex-wrap items-center gap-3 max-w-2xl">
                    <div class="relative flex-1 min-w-[220px]">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text"
                               x-model="search"
                               x-on:input.debounce.350ms="applyFilter()"
                               placeholder="Search user name or email address..."
                               class="w-full rounded-xl border-[#E2E8F0] text-sm pl-9 pr-3.5 py-2 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <!-- Custom Searchable Dropdown for Role Filter -->
                    <div class="w-48" x-data="{ open: false, q: '' }">
                        <input type="hidden" name="filter_role" :value="role">
                        <div class="relative">
                            <button type="button"
                                    @click="open = !open; if (open) $nextTick(() => $refs.roleFilterInput.focus())"
                                    class="w-full rounded-xl border border-[#E2E8F0] bg-white text-sm pl-3.5 pr-9 py-2 text-left relative flex items-center justify-between focus:outline-none focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB] transition">
                                <span class="truncate"
                                      :class="role ? 'text-slate-900 font-medium' : 'text-slate-600'"
                                      x-text="findFilterRoleLabel(role)"></span>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </button>

                            <div x-show="open" x-cloak @click.outside="open = false"
                                 x-transition
                                 class="absolute z-[60] mt-1.5 w-full bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                                <div class="p-2 border-b border-slate-100 bg-slate-50/70">
                                    <input type="text" x-ref="roleFilterInput" x-model="q"
                                           placeholder="Search role..."
                                           class="w-full rounded-lg border-slate-200 text-xs px-3 py-1.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                </div>
                                <div class="max-h-52 overflow-y-auto divide-y divide-slate-50 text-xs">
                                    <template x-for="opt in filterRolesForBar(q)" :key="opt.value">
                                        <button type="button"
                                                @click="role = opt.value; open = false; q = ''; applyFilter()"
                                                class="w-full px-3.5 py-2.5 text-left hover:bg-blue-50/60 flex items-center justify-between transition"
                                                :class="role === opt.value ? 'bg-blue-50/80 font-semibold text-[#2563EB]' : 'text-slate-700'">
                                            <span class="truncate" x-text="opt.label"></span>
                                        </button>
                                    </template>
                                    <div x-show="filterRolesForBar(q).length === 0" class="px-3.5 py-4 text-center text-slate-400">
                                        No matching role found.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button"
                            x-show="search !== '' || role !== ''"
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
                    <span>Add User</span>
                </button>
            </div>
        </div>

        <!-- Users Table Container -->
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
                            <th class="py-3.5 px-4 font-semibold">Full Name</th>
                            <th class="py-3.5 px-4 font-semibold">Email Address</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Role</th>
                            <th class="py-3.5 px-4 font-semibold">Joined Date</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($users as $user)
                            @php
                                $roleInfo = match($user->role) {
                                    'admin' => ['label' => 'Administrator', 'class' => 'bg-blue-100 text-[#2563EB]'],
                                    'gudang' => ['label' => 'Warehouse', 'class' => 'bg-emerald-100 text-[#059669]'],
                                    default => ['label' => 'Cashier', 'class' => 'bg-amber-100 text-[#D97706]'],
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center uppercase">
                                            {{ substr($user->name, 0, 2) }}
                                        </div>
                                        <div>
                                            <span class="font-semibold text-slate-900">{{ $user->name }}</span>
                                            @if(auth()->id() === $user->id)
                                                <span class="ml-1.5 px-2 py-0.5 text-[10px] font-bold rounded bg-slate-100 text-slate-600">You</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600">
                                    {{ $user->email }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $roleInfo['class'] }}">
                                        {{ $roleInfo['label'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-500">
                                    {{ $user->created_at?->format('d M Y') }}
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button"
                                                @click="openEditModal(@js($user->only(['id', 'name', 'email', 'role'])))"
                                                class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-xs font-semibold text-slate-700 transition">
                                            Edit
                                        </button>
                                        @if(auth()->id() !== $user->id)
                                            <button type="button"
                                                    @click="$dispatch('open-delete-modal', {
                                                        action: '{{ route('users.destroy', $user) }}',
                                                        title: 'Delete User Account',
                                                        itemName: @js($user->name)
                                                    })"
                                                    class="px-2.5 py-1.5 rounded-lg border border-rose-200 hover:bg-rose-50 text-xs font-semibold text-[#E11D48] transition">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 px-4 text-center text-sm text-slate-500">
                                    No user accounts matched your filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-[#E2E8F0] bg-slate-50/50">
                    {{ $users->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>

        <!-- Create / Edit User Modal -->
        <div x-show="modalOpen" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="modalOpen"
                     x-transition.opacity
                     @click="modalOpen = false"
                     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>

                <div x-show="modalOpen"
                     x-transition
                     class="relative w-full max-w-xl bg-white border border-[#E2E8F0] rounded-2xl shadow-xl p-6 sm:p-8 z-10 my-8 overflow-visible">
                    <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900" x-text="modalMode === 'edit' ? 'Edit User Account' : 'Add New User'"></h2>
                            <p class="text-xs text-slate-500 mt-0.5" x-text="modalMode === 'edit' ? 'Update staff profile details, role assignment, or password.' : 'Create a new staff account and assign system permissions.'"></p>
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
                            <label for="modal_user_name" class="block text-sm font-semibold text-slate-700 mb-1">
                                Full Name <span class="text-[#E11D48]">*</span>
                            </label>
                            <input type="text" id="modal_user_name" name="name" x-model="form.name" required
                                   placeholder="e.g. Andi Pratama"
                                   class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div>
                            <label for="modal_user_email" class="block text-sm font-semibold text-slate-700 mb-1">
                                Email Address <span class="text-[#E11D48]">*</span>
                            </label>
                            <input type="email" id="modal_user_email" name="email" x-model="form.email" required
                                   placeholder="andi@gadgetmania.test"
                                   class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>

                        <!-- User Role Custom Searchable Dropdown -->
                        <div x-data="{ open: false, q: '' }">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">
                                User Role (RBAC) <span class="text-[#E11D48]">*</span>
                            </label>
                            <input type="hidden" name="role" :value="form.role" required>

                            <div class="relative">
                                <button type="button"
                                        @click="open = !open; if (open) $nextTick(() => $refs.modalRoleInput.focus())"
                                        class="w-full rounded-xl border border-[#E2E8F0] bg-white text-sm pl-3.5 pr-9 py-2.5 text-left relative flex items-center justify-between focus:outline-none focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB] transition">
                                    <span class="truncate"
                                          :class="form.role ? 'text-slate-900 font-medium' : 'text-slate-400'"
                                          x-text="findModalRoleLabel(form.role)"></span>
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </span>
                                </button>

                                <div x-show="open" x-cloak @click.outside="open = false"
                                     x-transition
                                     class="absolute z-[60] mt-1.5 w-full bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                                    <div class="p-2 border-b border-slate-100 bg-slate-50/70">
                                        <input type="text" x-ref="modalRoleInput" x-model="q"
                                               placeholder="Search system role..."
                                               class="w-full rounded-lg border-slate-200 text-xs px-3 py-1.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                    </div>
                                    <div class="max-h-52 overflow-y-auto divide-y divide-slate-50 text-xs">
                                        <template x-for="opt in filterRolesForModal(q)" :key="opt.value">
                                            <button type="button"
                                                    @click="form.role = opt.value; open = false; q = ''"
                                                    class="w-full px-3.5 py-2.5 text-left hover:bg-blue-50/60 flex items-center justify-between transition"
                                                    :class="form.role === opt.value ? 'bg-blue-50/80 font-semibold text-[#2563EB]' : 'text-slate-700'">
                                                <span class="truncate" x-text="opt.label"></span>
                                            </button>
                                        </template>
                                        <div x-show="filterRolesForModal(q).length === 0" class="px-3.5 py-4 text-center text-slate-400">
                                            No matching role found.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('role')" class="mt-1" />
                        </div>

                        <div class="pt-3 border-t border-slate-100">
                            <p class="text-xs font-semibold text-slate-500 mb-3"
                               x-text="modalMode === 'edit' ? 'Change Password (Leave blank to keep current password)' : 'Set Account Password'"></p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="modal_user_password" class="block text-sm font-semibold text-slate-700 mb-1">
                                        Password <span x-show="modalMode === 'create'" class="text-[#E11D48]">*</span>
                                    </label>
                                    <input type="password" id="modal_user_password" name="password" x-model="form.password"
                                           :required="modalMode === 'create'"
                                           placeholder="Min. 8 characters"
                                           class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                                </div>

                                <div>
                                    <label for="modal_user_password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1">
                                        Confirm Password <span x-show="modalMode === 'create'" class="text-[#E11D48]">*</span>
                                    </label>
                                    <input type="password" id="modal_user_password_confirmation" name="password_confirmation" x-model="form.password_confirmation"
                                           :required="modalMode === 'create'"
                                           placeholder="Repeat password"
                                           class="w-full rounded-xl border-[#E2E8F0] text-sm px-3.5 py-2.5 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-[#E2E8F0] flex items-center justify-end gap-3">
                            <button type="button" @click="modalOpen = false"
                                    class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-5 py-2.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition"
                                    x-text="modalMode === 'edit' ? 'Save Changes' : 'Create User'">
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
