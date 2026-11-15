<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_dashboard_renders_seven_day_sales_trend_chart(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('7-Day Sales Revenue & Volume Trend', false);
        $response->assertSee('dashboardSalesChart', false);
    }

    public function test_admin_can_access_all_report_tabs_charts_and_custom_dropdowns(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $category = Category::firstOrFail();

        $response = $this->actingAs($admin)->get(route('reports.index', [
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->toDateString(),
            'category_id' => $category->id,
        ]));

        $response->assertOk();
        $response->assertSee('Reports & Sales Trend Analytics', false);
        $response->assertSee('Sales Trend & Sales Report', false);
        $response->assertSee('Current Stock & Valuation Report', false);
        $response->assertSee('Purchase Order & Procurement Report', false);
        $response->assertSee('Gadget Mania — Inventory & Financial Report', false);
        $response->assertSee('@page', false);
        $response->assertSee('print:px-10 print:py-8', false);
        $response->assertSee('Export CSV', false);
        $response->assertSee('Print Report', false);
        $response->assertDontSee('<select', false);
        $response->assertSee('absolute z-[60]', false);

        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('+07:00', config('database.connections.mysql.timezone'));
    }

    public function test_warehouse_only_sees_stock_and_purchase_reports_and_cannot_export_sales_csv(): void
    {
        $warehouse = User::where('role', 'gudang')->firstOrFail();

        $response = $this->actingAs($warehouse)->get(route('reports.index'));
        $response->assertOk();
        $response->assertSee('Current Stock & Valuation Report', false);
        $response->assertSee('Purchase Order & Procurement Report', false);
        $response->assertDontSee('Sales Trend & Sales Report', false);

        // Warehouse can export Stock and Purchases CSV
        $stockCsv = $this->actingAs($warehouse)->get(route('reports.export-csv', ['tab' => 'stock']));
        $stockCsv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $stockCsv->headers->get('Content-Type'));

        $poCsv = $this->actingAs($warehouse)->get(route('reports.export-csv', ['tab' => 'purchases']));
        $poCsv->assertOk();

        // Warehouse is forbidden from exporting Sales CSV
        $salesCsv = $this->actingAs($warehouse)->get(route('reports.export-csv', ['tab' => 'sales']));
        $salesCsv->assertForbidden();
    }

    public function test_cashier_only_sees_sales_reports_and_cannot_export_stock_or_po_csv(): void
    {
        $cashier = User::where('role', 'kasir')->firstOrFail();

        $response = $this->actingAs($cashier)->get(route('reports.index'));
        $response->assertOk();
        $response->assertSee('Sales Trend & Sales Report', false);
        $response->assertDontSee("switchTab('stock')", false);
        $response->assertDontSee("switchTab('purchases')", false);
        $response->assertDontSee('Total Active SKUs', false);
        $response->assertDontSee('Total Procurement Spend', false);

        // Cashier can export Sales CSV
        $salesCsv = $this->actingAs($cashier)->get(route('reports.export-csv', ['tab' => 'sales']));
        $salesCsv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $salesCsv->headers->get('Content-Type'));

        // Cashier is forbidden from exporting Stock or Purchases CSV
        $this->actingAs($cashier)->get(route('reports.export-csv', ['tab' => 'stock']))->assertForbidden();
        $this->actingAs($cashier)->get(route('reports.export-csv', ['tab' => 'purchases']))->assertForbidden();
    }

    public function test_csv_exports_contain_valid_headers_and_data_rows(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $salesCsv = $this->actingAs($admin)->get(route('reports.export-csv', ['tab' => 'sales']));
        $salesCsv->assertOk();
        $salesContent = $salesCsv->streamedContent();
        $this->assertStringContainsString('Reference Code', $salesContent);
        $this->assertStringContainsString('Estimated Gross Profit (Rp)', $salesContent);

        $stockCsv = $this->actingAs($admin)->get(route('reports.export-csv', ['tab' => 'stock']));
        $stockCsv->assertOk();
        $stockContent = $stockCsv->streamedContent();
        $this->assertStringContainsString('Inventory Asset Value (Rp)', $stockContent);
        $this->assertStringContainsString('Potential Retail Value (Rp)', $stockContent);

        $poCsv = $this->actingAs($admin)->get(route('reports.export-csv', ['tab' => 'purchases']));
        $poCsv->assertOk();
        $poContent = $poCsv->streamedContent();
        $this->assertStringContainsString('PO Number', $poContent);
        $this->assertStringContainsString('Total Amount (Rp)', $poContent);
    }
}
