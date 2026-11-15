# 📦 Gadget Mania — Enterprise Electronics Inventory System

Sistem Informasi Inventori Barang berbasis Web untuk **Toko Elektronik "Gadget Mania"** yang dibangun menggunakan **Laravel 11**, **MySQL 8.0**, **Tailwind CSS**, **Alpine.js**, dan **Chart.js**, serta dilengkapi konfigurasi containerization **Docker (Nginx + PHP-FPM + MySQL)**.

Proyek ini disusun untuk memenuhi Ujian Akhir Semester (Studi Kasus 1 — NPM Ganjil) Mata Kuliah **Deployment & Pemeliharaan Perangkat Lunak**.

---

## 👨‍🎓 Identitas Pengembang

| Atribut              | Keterangan                               |
| :------------------- | :--------------------------------------- |
| **Nama Mahasiswa**   | Fajar Try Sutrasno                       |
| **NPM**              | 41155050210053                           |
| **Kelas**            | C (Karyawan)                             |
| **Program Studi**    | Teknik Informatika — Fakultas Teknik     |
| **Perguruan Tinggi** | Universitas Langlangbuana (UNLA) Bandung |
| **Dosen Pengampu**   | Dr. Yiyi Supendi, S.Kom., M.T.           |

---

## ✨ Fitur Utama Sistem

1. **Role-Based Access Control (RBAC)**
   - Pembagian hak akses terproteksi middleware untuk 3 peran: **Administrator**, **Warehouse (Gudang)**, dan **Cashier (Kasir)**.
2. **Real-Time Inventory & Master Data Management**
   - Pengelolaan katalog produk elektronik (*Smartphone*, *Laptop*, *Tablet*, *Aksesoris*), kategori, dan data supplier menggunakan modal interaktif, *live search* (`debounce`), serta *custom searchable dropdown*.
3. **Automated Low Stock Alert & Smart Purchase Order (PO)**
   - Notifikasi peringatan stok rendah otomatis ketika `stock <= min_stock`.
   - Pintasan **"+ Create PO"** dari Dashboard yang otomatis memilih *Primary Supplier* dan menghitung kuantitas pesanan dengan rumus `(min_stock - stock) + 1`.
   - Saat status PO diubah menjadi **Received**, sistem secara transaksional (`DB::transaction()`) menambah stok produk dan mencatat jurnal *Stock In*.
4. **Multi-Item Retail Sales / Stock Out**
   - Pencatatan transaksi penjualan dengan validasi ketersediaan stok secara *real-time* (`lockForUpdate()`) untuk mencegah stok minus.
5. **Reports, Valuation & Sales Trend Analytics (Chart.js)**
   - Visualisasi grafik *Daily Sales Revenue & Units Trend* dan *Sales by Category*.
   - Laporan Penjualan, Valuasi Aset Stok, dan Laporan Pengadaan PO lengkap dengan fitur **Export CSV** dan **Print Report**.

---

## 🔐 Akun Demo Pengujian (RBAC)

| Peran (Role)           | Email Login               | Password   | Hak Akses Utama                                                                 |
| :--------------------- | :------------------------ | :--------- | :------------------------------------------------------------------------------ |
| **Administrator**      | `admin@gadgetmania.test`  | `password` | Akses penuh seluruh modul, laporan, dan manajemen pengguna                      |
| **Warehouse (Gudang)** | `gudang@gadgetmania.test` | `password` | Master Produk, Kategori, Supplier, Purchase Orders (Stok Masuk), & Laporan Stok |
| **Cashier (Kasir)**    | `kasir@gadgetmania.test`  | `password` | Katalog Produk (*View*), Sales / Stock Out, & Laporan Penjualan                 |

---

## 🚀 Panduan Deployment & Instalasi

Aplikasi mendukung **2 metode deployment**: menggunakan **Docker Container (Direkomendasikan untuk Production/Server)** atau **Instalasi Lokal (Native PHP/MySQL)**.

### Metode 1: Deployment Menggunakan Docker (Nginx + PHP-FPM + MySQL)

Pastikan **Docker Desktop** sudah terpasang dan aktif, kemudian jalankan:

```bash
# 1. Clone repositori
git clone https://github.com/jayenn11/gadget-mania.git
cd gadget-mania

# 2. Salin konfigurasi environment
cp .env.example .env

# 3. Bangun dan jalankan seluruh container (Nginx, PHP-FPM, MySQL)
docker compose up -d --build

# 4. Instal dependensi & jalankan migrasi + seeder di dalam container aplikasi
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
```

Akses aplikasi melalui peramban di: **`http://localhost:8080`**

---

### Metode 2: Instalasi Lokal (Native Development)

Pastikan komputer memiliki **PHP 8.2+**, **Composer**, **Node.js & NPM**, serta **MySQL 8.0**:

```bash
# 1. Clone repositori & masuk ke direktori proyek
git clone https://github.com/jayenn11/gadget-mania.git
cd gadget-mania

# 2. Instal dependensi PHP & Frontend
composer install
npm install

# 3. Salin file environment & generate application key
cp .env.example .env
php artisan key:generate
```

4. Buat database baru bernama **`gadget_mania_db`** di MySQL / TablePlus, lalu sesuaikan `.env`:

```env
APP_TIMEZONE=Asia/Jakarta
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gadget_mania_db
DB_USERNAME=root
DB_PASSWORD=password_mysql_anda
```

5. Jalankan migrasi beserta data awal (*seeder*) atau impor berkas `gadget_mania_db.sql`:

```bash
php artisan migrate:fresh --seed
```

6. Kompilasi aset frontend dan jalankan server:

```bash
npm run build
php artisan serve
```

7. Akses aplikasi di: **`[http://127.0.0.1:8000](http://127.0.0.1:8000)`**