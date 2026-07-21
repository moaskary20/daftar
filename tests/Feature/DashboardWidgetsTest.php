<?php

namespace Tests\Feature;

use App\Filament\Widgets\LatestCustomersTable;
use App\Filament\Widgets\LatestSuppliersTable;
use App\Filament\Widgets\LowStockProductsTable;
use App\Filament\Widgets\MonthlyProfitChart;
use App\Filament\Widgets\SalesTodayOverview;
use App\Filament\Widgets\SalesTrendChart;
use App\Filament\Widgets\TopSellingProductsTable;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesDocument;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_widgets_render_with_sales_data(): void
    {
        $manager = User::factory()->create();
        $role = Role::query()->create(['name' => 'المدير', 'slug' => 'manager']);
        $manager->roles()->attach($role);
        $this->actingAs($manager);

        $warehouse = Warehouse::query()->create(['name' => 'الرئيسي', 'code' => 'MAIN']);
        $product = Product::query()->create([
            'name' => 'منتج لوحة التحكم',
            'slug' => 'dashboard-product',
            'selling_price' => 100,
            'average_cost' => 60,
        ]);
        $customer = Customer::query()->create(['name' => 'عميل لوحة التحكم']);
        Supplier::query()->create(['name' => 'مورد لوحة التحكم']);
        app(InventoryService::class)->adjust($warehouse, $product, 10, StockMovement::TYPE_RECEIPT);

        $document = SalesDocument::query()->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'type' => SalesDocument::TYPE_INVOICE,
            'document_date' => today(),
        ]);
        $document->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 100,
            'tax_rate' => 0,
        ]);
        $document->recalculateTotals();
        app(SalesService::class)->post($document->fresh(['items', 'customer', 'sourceDocument']));

        WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->update(['reorder_level' => 20]);

        $this->get('/admin')->assertOk();

        Livewire::test(SalesTodayOverview::class)
            ->assertSee('مبيعات اليوم')
            ->assertSee('300.00');

        Livewire::test(SalesTrendChart::class)->assertOk();
        Livewire::test(MonthlyProfitChart::class)->assertOk();

        Livewire::test(TopSellingProductsTable::class)
            ->assertSee('منتج لوحة التحكم');

        Livewire::test(LowStockProductsTable::class)
            ->assertSee('منتج لوحة التحكم');

        Livewire::test(LatestCustomersTable::class)
            ->assertSee('عميل لوحة التحكم');

        Livewire::test(LatestSuppliersTable::class)
            ->assertSee('مورد لوحة التحكم');
    }
}
