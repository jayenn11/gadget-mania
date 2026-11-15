# AGENTS.md - Standar & Aturan Pengembangan Gadget Mania Inventory

## 1. Peran & Konteks Proyek
- **Nama Aplikasi:** Sistem Inventori Barang Toko Elektronik "Gadget Mania"
- **Tumpukan Teknologi:** Laravel 11 (PHP 8.2+), MySQL 8.0 (`gadget_mania_db`), Blade Engine, Tailwind CSS, Alpine.js, dan Chart.js.
- **Peran Agen:** Bertindak sebagai Senior Full-Stack Laravel Developer yang mengutamakan integritas data transaksi inventori, keamanan kontrol akses berbasis peran (RBAC), serta kebersihan arsitektur MVC.

## 2. Aturan Teknis & Integritas Data
- **Database Transactions:** Seluruh proses yang mengubah jumlah stok (`stock` pada tabel `products`)—baik melalui pencatatan stok masuk, penjualan/stok keluar, maupun penerimaan Purchase Order (PO)—WAJIB dibungkus di dalam `DB::transaction(function () { ... })` untuk mencegah inkonsistensi stok.
- **Validasi Stok Keluar:** Sistem wajib menolak transaksi penjualan/stok keluar apabila jumlah kuantitas melebihi stok barang yang tersedia saat ini.
- **Role-Based Access Control (RBAC):**
  - Gunakan middleware kustom (misal `role:admin,gudang`) untuk memproteksi rute di tingkat backend, tidak hanya menyembunyikan tombol di tampilan frontend.
- **Pencegahan Kedipan UI:**
  - Tambahkan `[x-cloak] { display: none !important; }` pada elemen modal atau dropdown berbasis Alpine.js.

## 3. Standar Antarmuka Pengguna (UI/UX)
- **Layout:** Clean Enterprise Dashboard (Sidebar navigasi di kiri dengan indikator peran aktif, Topbar info pengguna & notifikasi stok rendah, serta area konten utama yang responsif).
- **Palet Warna:**
  - Latar Utama: Slate Lembut (`#F8FAFC`) dan Kartu Putih (`#FFFFFF`) dengan border halus (`#E2E8F0`).
  - Warna Primer: Biru Teknologi (`#2563EB` / `bg-blue-600`).
  - Status Stok Aman: Hijau Emerald (`#059669`).
  - Status Stok Rendah / Peringatan: Amber/Merah (`#D97706` / `#E11D48`).
- **Bahasa:** Seluruh label antarmuka, judul halaman, menu navigasi, tombol, pesan validasi, dan flash message wajib menggunakan **Enterprise English** yang konsisten dan profesional.
