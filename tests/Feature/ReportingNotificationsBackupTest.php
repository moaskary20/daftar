<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesDocument;
use App\Models\StockMovement;
use App\Models\Treasury;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\AccountingService;
use App\Services\BackupService;
use App\Services\InventoryService;
use App\Services\NotificationService;
use App\Services\ReportingService;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportingNotificationsBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_report_calculates_gross_net_profit_and_margin(): void
    {
        [$warehouse, $product, $customer] = $this->salesFixtures();
        $document = $this->postInvoice($warehouse, $product, $customer, 2, 100);
        $category = ExpenseCategory::query()->create([
            'chart_account_id' => app(AccountingService::class)->systemAccount('5100')->id,
            'name' => 'مصروف',
            'code' => 'EXP',
        ]);
        Expense::query()->create([
            'expense_category_id' => $category->id,
            'amount' => 20,
            'expense_date' => today(),
            'payment_fund_type' => 'treasury',
            'payment_fund_id' => 1,
            'status' => 'posted',
            'description' => 'مصروف اختبار',
        ]);

        $summary = app(ReportingService::class)->salesSummary(today(), today());

        $this->assertSame(200.0, $summary['sales_total']);
        $this->assertSame(120.0, $summary['cost_total']);
        $this->assertSame(80.0, $summary['gross_profit']);
        $this->assertSame(60.0, $summary['net_profit']);
        $this->assertSame(40.0, $summary['profit_margin']);
        $this->assertSame($document->id, SalesDocument::query()->first()->id);
    }

    public function test_inventory_reports_return_movements_top_selling_stagnant_and_low_stock(): void
    {
        [$warehouse, $product, $customer] = $this->salesFixtures();
        $this->postInvoice($warehouse, $product, $customer, 2, 100);
        $stagnant = Product::query()->create([
            'name' => 'صنف راكد',
            'slug' => 'stagnant',
            'selling_price' => 20,
        ]);
        app(InventoryService::class)->adjust($warehouse, $stagnant, 5, StockMovement::TYPE_RECEIPT);
        WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->update(['reorder_level' => 8]);

        $service = app(ReportingService::class);

        $this->assertNotEmpty($service->itemMovement($product->id, today(), today()));
        $this->assertSame($product->id, (int) $service->topSellingProducts(today(), today())->first()->id);
        $this->assertTrue($service->stagnantProducts()->pluck('product_id')->contains($stagnant->id));
        $this->assertTrue($service->lowStockProducts()->pluck('product_id')->contains($product->id));
    }

    public function test_notification_generation_detects_expiry_stock_debt_and_overdue_invoice(): void
    {
        [$warehouse, $product, $customer] = $this->salesFixtures();
        $document = $this->postInvoice($warehouse, $product, $customer, 1, 100);
        $document->update(['expected_date' => today()->subDay()]);
        $stock = WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->firstOrFail();
        $stock->update(['quantity' => 0, 'reorder_level' => 2]);
        InventoryBatch::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'batch_number' => 'EXP-1',
            'expiry_date' => today()->addDays(10),
            'quantity' => 3,
        ]);

        app(NotificationService::class)->generateAll();
        $types = \App\Models\SystemNotification::query()->pluck('type');

        $this->assertTrue($types->contains('expiry'));
        $this->assertTrue($types->contains('out_of_stock'));
        $this->assertTrue($types->contains('customer_debt'));
        $this->assertTrue($types->contains('overdue_invoice'));
    }

    public function test_financial_reports_build_trial_balance_income_balance_sheet_and_cash_flow(): void
    {
        $accounting = app(AccountingService::class);
        $cash = $accounting->systemAccount('1101');
        $sales = $accounting->systemAccount('4100');
        $expenseAccount = $accounting->systemAccount('5100');
        Treasury::query()->create([
            'chart_account_id' => $cash->id,
            'name' => 'الخزينة',
            'code' => 'MAIN',
            'current_balance' => 400,
        ]);
        $accounting->createAndPost('إيراد', [
            ['account' => $cash, 'debit' => 500],
            ['account' => $sales, 'credit' => 500],
        ]);
        $accounting->createAndPost('مصروف', [
            ['account' => $expenseAccount, 'debit' => 100],
            ['account' => $cash, 'credit' => 100],
        ]);

        $service = app(ReportingService::class);
        $trial = $service->trialBalance(today(), today());
        $income = $service->incomeStatement(today(), today());
        $balance = $service->balanceSheet(today());
        $cashFlow = $service->cashFlow(today(), today());

        $this->assertSame(500.0, (float) $trial->firstWhere('code', '4100')['credit']);
        $this->assertSame(400.0, $income['net_income']);
        $this->assertSame(400.0, $balance['total_assets']);
        $this->assertSame(400.0, $balance['total_equity']);
        $this->assertSame(400.0, $cashFlow['net_cash_flow']);
    }

    public function test_backup_service_creates_downloadable_archive_and_record(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'المدير', 'slug' => 'manager']);
        $user->roles()->attach($role);
        $this->actingAs($user);

        $backup = app(BackupService::class)->create();

        $this->assertSame('completed', $backup->status);
        $this->assertGreaterThan(0, $backup->size);
        Storage::disk('local')->assertExists($backup->path);
        $this->get(route('backups.download', $backup))->assertOk();

        app(BackupService::class)->delete($backup);
        Storage::disk('local')->assertMissing($backup->path);
    }

    private function salesFixtures(): array
    {
        $warehouse = Warehouse::query()->create(['name' => 'الرئيسي', 'code' => 'MAIN']);
        $product = Product::query()->create([
            'name' => 'منتج تقارير',
            'slug' => 'report-product',
            'selling_price' => 100,
            'average_cost' => 60,
        ]);
        $customer = Customer::query()->create(['name' => 'عميل تقارير', 'credit_limit' => 1000]);
        app(InventoryService::class)->adjust($warehouse, $product, 10, StockMovement::TYPE_RECEIPT);

        return [$warehouse, $product, $customer];
    }

    private function postInvoice(
        Warehouse $warehouse,
        Product $product,
        Customer $customer,
        float $quantity,
        float $price,
    ): SalesDocument {
        $document = SalesDocument::query()->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'type' => SalesDocument::TYPE_INVOICE,
            'document_date' => today(),
        ]);
        $document->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $price,
            'tax_rate' => 0,
        ]);
        $document->recalculateTotals();
        app(SalesService::class)->post($document->fresh(['items', 'customer', 'sourceDocument']));

        return $document->fresh();
    }
}
