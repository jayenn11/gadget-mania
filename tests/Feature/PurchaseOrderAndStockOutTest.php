<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderAndStockOutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_warehouse_can_create_process_and_receive_purchase_order_updating_stock(): void
    {
        $warehouse = User::where('role', 'gudang')->firstOrFail();
        $supplier = Supplier::firstOrFail();
        $product1 = Product::where('sku', 'GM-S24-256')->firstOrFail(); // Initial stock: 3
        $product2 = Product::where('sku', 'GM-ROG-G14')->firstOrFail(); // Initial stock: 2

        $initialStock1 = $product1->stock;
        $initialStock2 = $product2->stock;

        // 1. Create Purchase Order
        $storeResponse = $this->actingAs($warehouse)->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'notes' => 'Restock low stock items',
            'items' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 5,
                    'unit_price' => 11900000, // Updated purchase price
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 3,
                    'unit_price' => 25500000,
                ],
            ],
        ]);

        $storeResponse->assertRedirect(route('purchase-orders.index'));

        $po = PurchaseOrder::latest('id')->firstOrFail();
        $this->assertMatchesRegularExpression('/^PO-\d{8}-\d{4}$/', $po->po_number);
        $this->assertSame('pending', $po->status);
        $this->assertEquals((5 * 11900000) + (3 * 25500000), (float) $po->total_amount);

        // 2. Mark as Processing
        $processResponse = $this->actingAs($warehouse)->patch(route('purchase-orders.process', $po));
        $processResponse->assertRedirect();
        $this->assertSame('processing', $po->fresh()->status);

        // 3. Receive Goods
        $receiveResponse = $this->actingAs($warehouse)->patch(route('purchase-orders.receive', $po));
        $receiveResponse->assertRedirect();
        $this->assertSame('received', $po->fresh()->status);

        // Verify product stock and purchase_price updated
        $this->assertSame($initialStock1 + 5, $product1->fresh()->stock);
        $this->assertEquals(11900000, (float) $product1->fresh()->purchase_price);
        $this->assertSame($initialStock2 + 3, $product2->fresh()->stock);

        // Verify stock_movements created
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product1->id,
            'type' => 'in',
            'quantity' => 5,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product2->id,
            'type' => 'in',
            'quantity' => 3,
        ]);

        // 4. Prevent duplicate receiving
        $duplicateReceive = $this->actingAs($warehouse)->patch(route('purchase-orders.receive', $po));
        $duplicateReceive->assertSessionHas('error');
        $this->assertSame($initialStock1 + 5, $product1->fresh()->stock);
    }

    public function test_cashier_can_record_multi_item_sale_and_rejects_excessive_quantity(): void
    {
        $cashier = User::where('role', 'kasir')->firstOrFail();
        $product = Product::where('sku', 'GM-IP15-128')->firstOrFail(); // Initial stock: 14
        $lowProduct = Product::where('sku', 'GM-ROG-G14')->firstOrFail(); // Initial stock: 2

        // 1. Attempt to sell more than available stock (should fail & rollback)
        $failResponse = $this->actingAs($cashier)->from(route('stock-outs.index'))->post(route('stock-outs.store'), [
            'notes' => 'Attempted oversell',
            'items' => [
                [
                    'product_id' => $lowProduct->id,
                    'quantity' => 5, // Only 2 available
                    'unit_price' => 28999000,
                ],
            ],
        ]);

        $failResponse->assertRedirect(route('stock-outs.index'));
        $failResponse->assertSessionHasErrors('items');
        $this->assertSame(2, $lowProduct->fresh()->stock);

        // 2. Record valid sale
        $validResponse = $this->actingAs($cashier)->post(route('stock-outs.store'), [
            'notes' => 'Walk-in retail sale',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price' => 13999000,
                ],
                [
                    'product_id' => $lowProduct->id,
                    'quantity' => 1,
                    'unit_price' => 28999000,
                ],
            ],
        ]);

        $validResponse->assertRedirect(route('stock-outs.index'));
        $this->assertSame(11, $product->fresh()->stock);
        $this->assertSame(1, $lowProduct->fresh()->stock);

        $latestOut = StockMovement::where('type', 'out')->latest('id')->firstOrFail();
        $this->assertMatchesRegularExpression('/^TRX-OUT-\d{8}-\d{4}$/', $latestOut->reference_code);
    }

    public function test_rbac_access_matrix_for_po_sales_and_stock_movements(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $warehouse = User::where('role', 'gudang')->firstOrFail();
        $cashier = User::where('role', 'kasir')->firstOrFail();

        // Stock Movements accessible by all roles
        $this->actingAs($admin)->get(route('stock-movements.index'))->assertOk();
        $this->actingAs($warehouse)->get(route('stock-movements.index'))->assertOk();
        $this->actingAs($cashier)->get(route('stock-movements.index'))->assertOk();

        // Purchase Orders accessible by Admin & Warehouse, blocked for Cashier
        $this->actingAs($admin)->get(route('purchase-orders.index'))->assertOk();
        $this->actingAs($warehouse)->get(route('purchase-orders.index'))->assertOk();
        $this->actingAs($cashier)->get(route('purchase-orders.index'))->assertForbidden();

        // Sales / Stock Out accessible by Admin & Cashier, blocked for Warehouse
        $this->actingAs($admin)->get(route('stock-outs.index'))->assertOk();
        $this->actingAs($cashier)->get(route('stock-outs.index'))->assertOk();
        $this->actingAs($warehouse)->get(route('stock-outs.index'))->assertForbidden();
    }

    public function test_create_po_from_low_stock_alert_autofills_supplier_and_calculated_quantity(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        // GM-S24-256 has stock = 3, min_stock = 5 => expected quantity = max(1, (5 - 3) + 1) = 3
        $lowProduct = Product::where('sku', 'GM-S24-256')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('purchase-orders.index', [
            'create' => 1,
            'product_id' => $lowProduct->id,
        ]));

        $response->assertOk();
        $response->assertSee("supplier_id: '" . $lowProduct->supplier_id . "'", false);
        $response->assertSee('\u0022quantity\u0022:3', false);
        $response->assertSee('bg-emerald-50 border-emerald-100 text-emerald-600', false);
    }

    public function test_stock_outs_and_movements_tables_have_proper_column_proportions(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $salesPage = $this->actingAs($admin)->get(route('stock-outs.index'));
        $salesPage->assertOk();
        $salesPage->assertSee('min-w-[240px]', false);

        $movementsPage = $this->actingAs($admin)->get(route('stock-movements.index'));
        $movementsPage->assertOk();
        $movementsPage->assertSee('min-w-[240px]', false);
    }

    public function test_all_modules_use_custom_searchable_dropdowns_and_overflow_visible_modals(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $routesWithDropdowns = [
            route('products.index'),
            route('purchase-orders.index'),
            route('stock-outs.index'),
            route('stock-movements.index'),
            route('users.index'),
        ];

        foreach ($routesWithDropdowns as $url) {
            $response = $this->actingAs($admin)->get($url);
            $response->assertOk();
            $response->assertDontSee('<select', false);
            $response->assertSee('absolute z-[60]', false);
        }

        $routesWithModals = [
            route('products.index'),
            route('categories.index'),
            route('suppliers.index'),
            route('purchase-orders.index'),
            route('stock-outs.index'),
            route('users.index'),
        ];

        foreach ($routesWithModals as $url) {
            $response = $this->actingAs($admin)->get($url);
            $response->assertOk();
            $response->assertSee('fixed inset-0 z-50 overflow-y-auto', false);
            $response->assertSee('overflow-visible', false);
        }
    }
}
