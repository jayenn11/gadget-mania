<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. User Accounts (RBAC)
            $admin = User::create([
                'name' => 'Store Administrator',
                'email' => 'admin@gadgetmania.test',
                'role' => 'admin',
                'password' => Hash::make('password'),
            ]);

            $gudang = User::create([
                'name' => 'Head Warehouse Staff',
                'email' => 'gudang@gadgetmania.test',
                'role' => 'gudang',
                'password' => Hash::make('password'),
            ]);

            $kasir = User::create([
                'name' => 'Retail Cashier 1',
                'email' => 'kasir@gadgetmania.test',
                'role' => 'kasir',
                'password' => Hash::make('password'),
            ]);

            // 2. Electronics Product Categories
            $catSmartphone = Category::create([
                'name' => 'Smartphone',
                'description' => '5G smartphones and flagship mobile devices across leading global brands.',
            ]);

            $catLaptop = Category::create([
                'name' => 'Laptop',
                'description' => 'Portable computers for enterprise productivity, creative workloads, and gaming.',
            ]);

            $catTablet = Category::create([
                'name' => 'Tablet',
                'description' => 'Touchscreen tablets for mobile productivity, digital illustration, and entertainment.',
            ]);

            $catAksesoris = Category::create([
                'name' => 'Accessories',
                'description' => 'Essential gadget peripherals including GaN chargers, wireless mice, keyboards, and audio.',
            ]);

            // 3. Electronics Suppliers (Numeric/Plus Phone Format)
            $supErajaya = Supplier::create([
                'code' => 'SUP-001',
                'name' => 'PT Erajaya Swasembada Distribusi',
                'contact_person' => 'Budi Santoso',
                'phone' => '02155967890',
                'email' => 'distribusi@erajaya-supply.co.id',
                'address' => 'Jl. Gedong Panjang No. 29-31, Pekojan, Tambora, West Jakarta',
            ]);

            $supMetrodata = Supplier::create([
                'code' => 'SUP-002',
                'name' => 'PT Metrodata Electronics',
                'contact_person' => 'Hendra Wijaya',
                'phone' => '02129345800',
                'email' => 'sales@metrodata.co.id',
                'address' => 'APL Tower 37th Floor, Jl. Letjen S. Parman Kav. 28, West Jakarta',
            ]);

            $supNusantara = Supplier::create([
                'code' => 'SUP-003',
                'name' => 'CV Nusantara Gadget Supply',
                'contact_person' => 'Rina Permatasari',
                'phone' => '0224231098',
                'email' => 'order@nusantaragadget.id',
                'address' => 'Jl. Purnawarman No. 13-15, Babakan Ciamis, Bandung',
            ]);

            $supBintang = Supplier::create([
                'code' => 'SUP-004',
                'name' => 'PT Bintang Aksesoris Nusantara',
                'contact_person' => 'Deni Kurniawan',
                'phone' => '0315349821',
                'email' => 'partnership@bintangaksesoris.co.id',
                'address' => 'Jl. Kayoon No. 42, Embong Kaliasin, Genteng, Surabaya',
            ]);

            // 4. Master Electronics Products (12 Products, 4 with low stock <= min_stock)
            $productsData = [
                // Smartphone
                [
                    'category_id' => $catSmartphone->id,
                    'supplier_id' => $supErajaya->id,
                    'sku' => 'GM-IP15-128',
                    'name' => 'iPhone 15 128GB Black',
                    'brand' => 'Apple',
                    'purchase_price' => 12200000,
                    'selling_price' => 13999000,
                    'stock' => 14,
                    'min_stock' => 5,
                    'unit' => 'Unit',
                ],
                [
                    'category_id' => $catSmartphone->id,
                    'supplier_id' => $supErajaya->id,
                    'sku' => 'GM-S24-256',
                    'name' => 'Samsung Galaxy S24 256GB Onyx Black',
                    'brand' => 'Samsung',
                    'purchase_price' => 11800000,
                    'selling_price' => 13499000,
                    'stock' => 3, // Low Stock (<= 5)
                    'min_stock' => 5,
                    'unit' => 'Unit',
                ],
                [
                    'category_id' => $catSmartphone->id,
                    'supplier_id' => $supNusantara->id,
                    'sku' => 'GM-XM14-256',
                    'name' => 'Xiaomi 14 5G 12/256GB Jade Green',
                    'brand' => 'Xiaomi',
                    'purchase_price' => 10400000,
                    'selling_price' => 11999000,
                    'stock' => 10,
                    'min_stock' => 4,
                    'unit' => 'Unit',
                ],
                // Laptop
                [
                    'category_id' => $catLaptop->id,
                    'supplier_id' => $supErajaya->id,
                    'sku' => 'GM-MBA-M3',
                    'name' => 'MacBook Air 13" M3 8/256GB Space Grey',
                    'brand' => 'Apple',
                    'purchase_price' => 15900000,
                    'selling_price' => 17999000,
                    'stock' => 8,
                    'min_stock' => 4,
                    'unit' => 'Unit',
                ],
                [
                    'category_id' => $catLaptop->id,
                    'supplier_id' => $supMetrodata->id,
                    'sku' => 'GM-ROG-G14',
                    'name' => 'ASUS ROG Zephyrus G14 OLED Ryzen 9 RTX4060',
                    'brand' => 'ASUS',
                    'purchase_price' => 25500000,
                    'selling_price' => 28999000,
                    'stock' => 2, // Low Stock (<= 3)
                    'min_stock' => 3,
                    'unit' => 'Unit',
                ],
                [
                    'category_id' => $catLaptop->id,
                    'supplier_id' => $supMetrodata->id,
                    'sku' => 'GM-LEN-LOQ15',
                    'name' => 'Lenovo LOQ 15 Core i5-13450HX RTX3050',
                    'brand' => 'Lenovo',
                    'purchase_price' => 11200000,
                    'selling_price' => 12799000,
                    'stock' => 9,
                    'min_stock' => 4,
                    'unit' => 'Unit',
                ],
                // Tablet
                [
                    'category_id' => $catTablet->id,
                    'supplier_id' => $supErajaya->id,
                    'sku' => 'GM-IPAD-M2',
                    'name' => 'iPad Air 11" M2 Wi-Fi 128GB Blue',
                    'brand' => 'Apple',
                    'purchase_price' => 9600000,
                    'selling_price' => 10999000,
                    'stock' => 4, // Low Stock (<= 5)
                    'min_stock' => 5,
                    'unit' => 'Unit',
                ],
                [
                    'category_id' => $catTablet->id,
                    'supplier_id' => $supNusantara->id,
                    'sku' => 'GM-TABS9-FE',
                    'name' => 'Samsung Galaxy Tab S9 FE Wi-Fi 6/128GB',
                    'brand' => 'Samsung',
                    'purchase_price' => 5400000,
                    'selling_price' => 6499000,
                    'stock' => 11,
                    'min_stock' => 4,
                    'unit' => 'Unit',
                ],
                [
                    'category_id' => $catTablet->id,
                    'supplier_id' => $supNusantara->id,
                    'sku' => 'GM-MIPAD-6',
                    'name' => 'Xiaomi Pad 6 8/256GB Gravity Gray',
                    'brand' => 'Xiaomi',
                    'purchase_price' => 4300000,
                    'selling_price' => 4999000,
                    'stock' => 12,
                    'min_stock' => 5,
                    'unit' => 'Unit',
                ],
                // Accessories
                [
                    'category_id' => $catAksesoris->id,
                    'supplier_id' => $supBintang->id,
                    'sku' => 'GM-LOGI-MX3S',
                    'name' => 'Logitech MX Master 3S Wireless Mouse',
                    'brand' => 'Logitech',
                    'purchase_price' => 1320000,
                    'selling_price' => 1649000,
                    'stock' => 18,
                    'min_stock' => 6,
                    'unit' => 'Pcs',
                ],
                [
                    'category_id' => $catAksesoris->id,
                    'supplier_id' => $supBintang->id,
                    'sku' => 'GM-ANK-65W',
                    'name' => 'Anker Nano II 65W GaN Fast Charger',
                    'brand' => 'Anker',
                    'purchase_price' => 420000,
                    'selling_price' => 599000,
                    'stock' => 5, // Low Stock (<= 8)
                    'min_stock' => 8,
                    'unit' => 'Pcs',
                ],
                [
                    'category_id' => $catAksesoris->id,
                    'supplier_id' => $supErajaya->id,
                    'sku' => 'GM-AIRPODS-P2',
                    'name' => 'Apple AirPods Pro (2nd Gen) USB-C',
                    'brand' => 'Apple',
                    'purchase_price' => 3350000,
                    'selling_price' => 3999000,
                    'stock' => 15,
                    'min_stock' => 5,
                    'unit' => 'Unit',
                ],
            ];

            $products = [];
            foreach ($productsData as $data) {
                $products[$data['sku']] = Product::create($data);
            }

            // 5. Sample Purchase Orders (3 POs: received, processing, pending)
            $po1 = PurchaseOrder::create([
                'po_number' => 'PO-20261109-0001',
                'supplier_id' => $supErajaya->id,
                'user_id' => $gudang->id,
                'order_date' => Carbon::now()->subDays(6)->toDateString(),
                'status' => 'received',
                'total_amount' => 140800000,
                'notes' => 'Monthly stock replenishment for iPhone 15 and MacBook Air M3.',
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po1->id,
                'product_id' => $products['GM-IP15-128']->id,
                'quantity' => 5,
                'unit_price' => 12200000,
                'subtotal' => 61000000,
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po1->id,
                'product_id' => $products['GM-MBA-M3']->id,
                'quantity' => 5,
                'unit_price' => 15960000,
                'subtotal' => 79800000,
            ]);

            $po2 = PurchaseOrder::create([
                'po_number' => 'PO-20261112-0002',
                'supplier_id' => $supMetrodata->id,
                'user_id' => $admin->id,
                'order_date' => Carbon::now()->subDays(3)->toDateString(),
                'status' => 'processing',
                'total_amount' => 95800000,
                'notes' => 'Restock gaming laptops ROG Zephyrus G14 and Lenovo LOQ 15.',
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po2->id,
                'product_id' => $products['GM-ROG-G14']->id,
                'quantity' => 2,
                'unit_price' => 25500000,
                'subtotal' => 51000000,
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po2->id,
                'product_id' => $products['GM-LEN-LOQ15']->id,
                'quantity' => 4,
                'unit_price' => 11200000,
                'subtotal' => 44800000,
            ]);

            $po3 = PurchaseOrder::create([
                'po_number' => 'PO-20261114-0003',
                'supplier_id' => $supBintang->id,
                'user_id' => $gudang->id,
                'order_date' => Carbon::now()->subDay()->toDateString(),
                'status' => 'pending',
                'total_amount' => 14700000,
                'notes' => 'Additional stock order for Anker fast chargers and Logitech mice.',
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po3->id,
                'product_id' => $products['GM-ANK-65W']->id,
                'quantity' => 20,
                'unit_price' => 420000,
                'subtotal' => 8400000,
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po3->id,
                'product_id' => $products['GM-LOGI-MX3S']->id,
                'quantity' => 5,
                'unit_price' => 1260000,
                'subtotal' => 6300000,
            ]);

            // 6. Sample Stock Movements (10 Movements over the last 7 days)
            $movements = [
                [
                    'reference_code' => 'TRX-IN-20261101',
                    'product_id' => $products['GM-IP15-128']->id,
                    'user_id' => $gudang->id,
                    'type' => 'in',
                    'quantity' => 5,
                    'unit_price' => 12200000,
                    'total_price' => 61000000,
                    'notes' => 'PO Receipt #PO-202611-001 (iPhone 15 128GB)',
                    'transaction_date' => Carbon::now()->subDays(6)->setTime(10, 15),
                ],
                [
                    'reference_code' => 'TRX-IN-20261102',
                    'product_id' => $products['GM-MBA-M3']->id,
                    'user_id' => $gudang->id,
                    'type' => 'in',
                    'quantity' => 5,
                    'unit_price' => 15900000,
                    'total_price' => 79500000,
                    'notes' => 'PO Receipt #PO-202611-001 (MacBook Air M3)',
                    'transaction_date' => Carbon::now()->subDays(6)->setTime(10, 20),
                ],
                [
                    'reference_code' => 'TRX-OUT-20261101',
                    'product_id' => $products['GM-IP15-128']->id,
                    'user_id' => $kasir->id,
                    'type' => 'out',
                    'quantity' => 2,
                    'unit_price' => 13999000,
                    'total_price' => 27998000,
                    'notes' => 'Retail store sale - Walk-in Customer',
                    'transaction_date' => Carbon::now()->subDays(5)->setTime(14, 30),
                ],
                [
                    'reference_code' => 'TRX-OUT-20261102',
                    'product_id' => $products['GM-S24-256']->id,
                    'user_id' => $kasir->id,
                    'type' => 'out',
                    'quantity' => 2,
                    'unit_price' => 13499000,
                    'total_price' => 26998000,
                    'notes' => 'Retail store sale - Samsung Galaxy S24',
                    'transaction_date' => Carbon::now()->subDays(4)->setTime(16, 10),
                ],
                [
                    'reference_code' => 'TRX-IN-20261103',
                    'product_id' => $products['GM-LOGI-MX3S']->id,
                    'user_id' => $gudang->id,
                    'type' => 'in',
                    'quantity' => 10,
                    'unit_price' => 1320000,
                    'total_price' => 13200000,
                    'notes' => 'Regular stock-in for Logitech peripherals',
                    'transaction_date' => Carbon::now()->subDays(3)->setTime(9, 45),
                ],
                [
                    'reference_code' => 'TRX-OUT-20261103',
                    'product_id' => $products['GM-MBA-M3']->id,
                    'user_id' => $kasir->id,
                    'type' => 'out',
                    'quantity' => 1,
                    'unit_price' => 17999000,
                    'total_price' => 17999000,
                    'notes' => 'Retail sale - MacBook Air M3 bundle',
                    'transaction_date' => Carbon::now()->subDays(3)->setTime(13, 20),
                ],
                [
                    'reference_code' => 'TRX-OUT-20261104',
                    'product_id' => $products['GM-ROG-G14']->id,
                    'user_id' => $kasir->id,
                    'type' => 'out',
                    'quantity' => 1,
                    'unit_price' => 28999000,
                    'total_price' => 28999000,
                    'notes' => 'Retail sale - ASUS ROG Zephyrus G14 gaming laptop',
                    'transaction_date' => Carbon::now()->subDays(2)->setTime(15, 5),
                ],
                [
                    'reference_code' => 'TRX-OUT-20261105',
                    'product_id' => $products['GM-IPAD-M2']->id,
                    'user_id' => $kasir->id,
                    'type' => 'out',
                    'quantity' => 2,
                    'unit_price' => 10999000,
                    'total_price' => 21998000,
                    'notes' => 'Corporate order - 2 units iPad Air M2',
                    'transaction_date' => Carbon::now()->subDay()->setTime(11, 40),
                ],
                [
                    'reference_code' => 'TRX-OUT-20261106',
                    'product_id' => $products['GM-AIRPODS-P2']->id,
                    'user_id' => $kasir->id,
                    'type' => 'out',
                    'quantity' => 3,
                    'unit_price' => 3999000,
                    'total_price' => 11997000,
                    'notes' => 'Retail sale - AirPods Pro (2nd Gen)',
                    'transaction_date' => Carbon::now()->subDay()->setTime(17, 15),
                ],
                [
                    'reference_code' => 'TRX-OUT-20261107',
                    'product_id' => $products['GM-ANK-65W']->id,
                    'user_id' => $kasir->id,
                    'type' => 'out',
                    'quantity' => 4,
                    'unit_price' => 599000,
                    'total_price' => 2396000,
                    'notes' => 'Retail sale - Anker 65W GaN Fast Charger',
                    'transaction_date' => Carbon::now()->setTime(10, 30),
                ],
            ];

            foreach ($movements as $movement) {
                StockMovement::create($movement);
            }
        });
    }
}
