<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-900">Sign In to Your Account</h1>
        <p class="text-sm text-slate-500 mt-1">Enter your credentials to access the inventory workspace.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-semibold text-slate-700 mb-1">
                Email Address <span class="text-[#E11D48]">*</span>
            </label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                placeholder="name@gadgetmania.test"
                class="w-full rounded-xl border-[#E2E8F0] bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-900 focus:bg-white focus:border-[#2563EB] focus:ring-[#2563EB] transition" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-semibold text-slate-700 mb-1">
                Password <span class="text-[#E11D48]">*</span>
            </label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                class="w-full rounded-xl border-[#E2E8F0] bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-900 focus:bg-white focus:border-[#2563EB] focus:ring-[#2563EB] transition" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-[#2563EB] shadow-sm focus:ring-[#2563EB]" name="remember">
                <span class="ms-2 text-sm text-slate-600">Remember me on this device</span>
            </label>
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-[#2563EB] hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                <span>Sign In</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </div>
    </form>

    <!-- Demo Credentials Box -->
    <div class="mt-6 pt-5 border-t border-[#E2E8F0]">
        <div class="flex items-center justify-between mb-2.5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Demo Accounts (Click to Autofill)</span>
            <span class="text-[11px] font-medium px-2 py-0.5 rounded bg-slate-100 text-slate-600">Password: <code>password</code></span>
        </div>
        <div class="space-y-2 text-xs">
            <button type="button" onclick="document.getElementById('email').value='admin@gadgetmania.test';document.getElementById('password').value='password';"
                class="w-full flex items-center justify-between p-2.5 rounded-xl border border-slate-200 hover:border-blue-400 hover:bg-blue-50/40 text-left transition">
                <div>
                    <span class="font-semibold text-slate-800 block">admin@gadgetmania.test</span>
                    <span class="text-slate-500">Full system access & user management</span>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-700">Administrator</span>
            </button>

            <button type="button" onclick="document.getElementById('email').value='gudang@gadgetmania.test';document.getElementById('password').value='password';"
                class="w-full flex items-center justify-between p-2.5 rounded-xl border border-slate-200 hover:border-emerald-400 hover:bg-emerald-50/40 text-left transition">
                <div>
                    <span class="font-semibold text-slate-800 block">gudang@gadgetmania.test</span>
                    <span class="text-slate-500">Products, categories, suppliers, PO & stock in</span>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700">Warehouse</span>
            </button>

            <button type="button" onclick="document.getElementById('email').value='kasir@gadgetmania.test';document.getElementById('password').value='password';"
                class="w-full flex items-center justify-between p-2.5 rounded-xl border border-slate-200 hover:border-amber-400 hover:bg-amber-50/40 text-left transition">
                <div>
                    <span class="font-semibold text-slate-800 block">kasir@gadgetmania.test</span>
                    <span class="text-slate-500">Catalog view & retail sales / stock out</span>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-700">Cashier</span>
            </button>
        </div>
    </div>
</x-guest-layout>
