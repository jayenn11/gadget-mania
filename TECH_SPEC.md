# TECH_SPEC.md - Cetak Biru Teknis Sistem Inventori "Gadget Mania"

## 1. Arsitektur Basis Data (`gadget_mania_db`)

### Tabel 1: `users` (Modifikasi bawaan Laravel)
- `id` (BIGINT, PK)
- `name` (VARCHAR 255)
- `email` (VARCHAR 255, Unique)
- `password` (VARCHAR 255)
- `role` (ENUM: `'admin'`, `'kasir'`, `'gudang'`, Default: `'kasir'`)

### Tabel 2: `categories` (Kategori Produk Elektronik)
- `id` (BIGINT, PK)
- `name` (VARCHAR 100) — *Contoh: Smartphone, Laptop, Tablet, Aksesoris*
- `description` (TEXT, Nullable)

### Tabel 3: `suppliers` (Data Supplier)
- `id` (BIGINT, PK)
- `code` (VARCHAR 20, Unique) — *Contoh: SUP-001*
- `name` (VARCHAR 150)
- `contact_person` (VARCHAR 100)
- `phone` (VARCHAR 25)
- `email` (VARCHAR 100, Nullable)
- `address` (TEXT)

### Tabel 4: `products` (Master Barang Elektronik)
- `id` (BIGINT, PK)
- `category_id` (FK -> `categories.id`, On Delete Restrict)
- `supplier_id` (FK -> `suppliers.id`, On Delete Set Null, Nullable)
- `sku` (VARCHAR 50, Unique) — *Contoh: GM-IP15-128*
- `name` (VARCHAR 200)
- `brand` (VARCHAR 100)
- `purchase_price` (DECIMAL 15,2) — *Harga beli dari supplier*
- `selling_price` (DECIMAL 15,2) — *Harga jual ritel*
- `stock` (INT, Default: 0) — *Stok real-time*
- `min_stock` (INT, Default: 5) — *Batas minimum untuk notifikasi stok rendah*
- `unit` (VARCHAR 20, Default: `'Unit'`)

### Tabel 5: `purchase_orders` (Pemesanan Barang ke Supplier)
- `id` (BIGINT, PK)
- `po_number` (VARCHAR 50, Unique) — *Contoh: PO-202611-001*
- `supplier_id` (FK -> `suppliers.id`, On Delete Restrict)
- `user_id` (FK -> `users.id`) — *Pembuat pesanan*
- `order_date` (DATE)
- `status` (ENUM: `'pending'`, `'diproses'`, `'diterima'`, `'dibatalkan'`, Default: `'pending'`)
- `total_amount` (DECIMAL 15,2, Default: 0)
- `notes` (TEXT, Nullable)

### Tabel 6: `purchase_order_items` (Detail Item PO)
- `id` (BIGINT, PK)
- `purchase_order_id` (FK -> `purchase_orders.id`, On Delete Cascade)
- `product_id` (FK -> `products.id`, On Delete Restrict)
- `quantity` (INT)
- `unit_price` (DECIMAL 15,2)
- `subtotal` (DECIMAL 15,2)

### Tabel 7: `stock_movements` (Riwayat Stok Masuk & Keluar / Penjualan)
- `id` (BIGINT, PK)
- `reference_code` (VARCHAR 50, Unique) — *Contoh: TRX-IN-001 / TRX-OUT-001*
- `product_id` (FK -> `products.id`, On Delete Restrict)
- `user_id` (FK -> `users.id`)
- `type` (ENUM: `'in'`, `'out'`) — *'in' = Stok Masuk/Pembelian, 'out' = Stok Keluar/Penjualan*
- `quantity` (INT)
- `unit_price` (DECIMAL 15,2) — *Harga transaksi saat kejadian*
- `total_price` (DECIMAL 15,2)
- `notes` (VARCHAR 255, Nullable)
- `transaction_date` (DATETIME)

---

## 2. Matriks Hak Akses Pengguna (RBAC)
| Modul / Fitur                             |   Admin    |      Gudang       |       Kasir       |
| :---------------------------------------- | :--------: | :---------------: | :---------------: |
| Dashboard & Notifikasi Stok Rendah        | Ya (Penuh) |  Ya (Fokus Stok)  |   Ya (Ringkas)    |
| Master Barang & Kategori (CRUD)           |     Ya     |        Ya         |    Lihat Saja     |
| Master Supplier & Riwayat Transaksi       |     Ya     |        Ya         |       Tidak       |
| Pemesanan Barang (Purchase Order)         |     Ya     |        Ya         |       Tidak       |
| Pencatatan Stok Masuk                     |     Ya     |        Ya         |       Tidak       |
| Pencatatan Stok Keluar / Penjualan        |     Ya     |       Tidak       |        Ya         |
| Laporan Stok, Pembelian, Penjualan & Tren |     Ya     | Laporan Stok & PO | Laporan Penjualan |
| Manajemen Akun Pengguna                   |     Ya     |       Tidak       |       Tidak       |

---

## 3. Spesifikasi Deployment (Target Mata Kuliah)
- Menyediakan konfigurasi **Docker** (`Dockerfile` & `docker-compose.yml` menggunakan Nginx, PHP-FPM 8.2, dan MySQL 8.0) agar aplikasi portabel dan siap di-*deploy* secara konsisten.
- Menyertakan dokumentasi langkah instalasi, migrasi, dan deployment pada `README.md`.
