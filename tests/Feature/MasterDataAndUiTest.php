<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataAndUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_page_uses_enterprise_english_and_demo_credentials(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sign In to Your Account');
        $response->assertSee('Demo Accounts (Click to Autofill)');
        $response->assertSee('admin@gadgetmania.test');
    }

    public function test_dashboard_renders_sidebar_and_low_stock_create_po_button(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Inventory Dashboard');
        $response->assertSee('Low Stock Alert');
        $response->assertSee('Recent Stock Activity');
        $response->assertSee('Create PO');
        $response->assertSee('whitespace-nowrap shrink-0 inline-flex items-center gap-1.5', false);
        $response->assertDontSee('PERAN AKTIF');
        $response->assertDontSee('ACTIVE ROLE');
    }

    public function test_products_live_filter_modal_unit_dropdown_and_light_pagination(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get('/products');
        $response->assertStatus(200);
        $response->assertSee('Add Product');
        $response->assertSee('Unit of Measure');
        // Check light pagination active state
        $response->assertSee('bg-blue-600 border border-blue-600', false);
        // Check custom delete modal dispatch
        $response->assertSee('open-delete-modal', false);

        // Live search query
        $filtered = $this->actingAs($admin)->get('/products?search=iPhone&stock_status=safe');
        $filtered->assertStatus(200);
        $filtered->assertSee('iPhone 15 128GB Black');

        // Store product with valid unit from dropdown
        $category = Category::firstOrFail();
        $supplier = Supplier::firstOrFail();

        $storeResponse = $this->actingAs($admin)->post('/products', [
            'sku' => 'GM-TEST-001',
            'name' => 'Sony WH-1000XM5 Wireless Headphones',
            'brand' => 'Sony',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'purchase_price' => 4200000,
            'selling_price' => 4999000,
            'stock' => 10,
            'min_stock' => 3,
            'unit' => 'Box',
        ]);

        $storeResponse->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'sku' => 'GM-TEST-001',
            'unit' => 'Box',
        ]);
    }

    public function test_supplier_phone_validation_enforces_numeric_only(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        // Invalid phone with letters/hyphens should fail
        $invalidResponse = $this->actingAs($admin)->from('/suppliers')->post('/suppliers', [
            'code' => 'SUP-999',
            'name' => 'PT Invalid Phone Supplier',
            'contact_person' => 'John Doe',
            'phone' => '021-555-ABCD',
            'email' => 'info@supplier.test',
            'address' => 'Jakarta',
        ]);

        $invalidResponse->assertRedirect('/suppliers');
        $invalidResponse->assertSessionHasErrors('phone');

        // Valid numeric/plus phone should succeed
        $validResponse = $this->actingAs($admin)->post('/suppliers', [
            'code' => 'SUP-005',
            'name' => 'PT Valid Phone Supplier',
            'contact_person' => 'Jane Doe',
            'phone' => '+622155512345',
            'email' => 'info@supplier.test',
            'address' => 'Jl. Sudirman No. 1, Jakarta',
        ]);

        $validResponse->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', [
            'code' => 'SUP-005',
            'phone' => '+622155512345',
        ]);
    }

    public function test_rbac_blocks_cashier_from_master_crud_with_english_403(): void
    {
        $cashier = User::where('role', 'kasir')->firstOrFail();

        // Cashier can view products catalog
        $this->actingAs($cashier)->get('/products')->assertStatus(200);

        // Cashier cannot access categories, suppliers, or users
        $response = $this->actingAs($cashier)->get('/suppliers');
        $response->assertStatus(403);
        $response->assertSee('Access Denied');
        $response->assertSee('You do not have permission to access this resource.');
    }
}
