<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseDocument;
use App\Models\StockMovement;
use App\Models\Stocktake;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryPurchasingTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_issue_and_transfer_keep_warehouse_balances_consistent(): void
    {
        [$main, $branch, $product] = $this->inventoryFixtures();
        $service = app(InventoryService::class);

        $service->adjust($main, $product, 10, StockMovement::TYPE_RECEIPT, unitCost: 20);
        $service->adjust($main, $product, -2, StockMovement::TYPE_ISSUE);

        $transfer = StockTransfer::query()->create([
            'from_warehouse_id' => $main->id,
            'to_warehouse_id' => $branch->id,
            'transfer_date' => today(),
        ]);
        $transfer->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $service->completeTransfer($transfer->fresh('items'));

        $this->assertSame(5.0, $this->stock($main, $product));
        $this->assertSame(3.0, $this->stock($branch, $product));
        $this->assertSame(8.0, (float) $product->fresh()->stock_quantity);
        $this->assertSame(StockTransfer::STATUS_COMPLETED, $transfer->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 4);
    }

    public function test_partial_and_full_stocktakes_prepare_and_post_differences(): void
    {
        [$main, $branch, $product] = $this->inventoryFixtures();
        $service = app(InventoryService::class);
        $service->adjust($main, $product, 10, StockMovement::TYPE_RECEIPT);

        $partial = Stocktake::query()->create([
            'warehouse_id' => $main->id,
            'type' => Stocktake::TYPE_PARTIAL,
            'stocktake_date' => today(),
        ]);
        $partial->items()->create(['product_id' => $product->id]);

        $service->prepareStocktake($partial->fresh('items'));
        $item = $partial->items()->first();
        $this->assertSame(10.0, (float) $item->expected_quantity);

        $item->update(['counted_quantity' => 7]);
        $service->completeStocktake($partial->fresh('items'));

        $this->assertSame(7.0, $this->stock($main, $product));
        $this->assertSame(Stocktake::STATUS_COMPLETED, $partial->fresh()->status);

        $full = Stocktake::query()->create([
            'warehouse_id' => $main->id,
            'type' => Stocktake::TYPE_FULL,
            'stocktake_date' => today(),
        ]);
        $service->prepareStocktake($full);

        $this->assertSame(1, $full->items()->count());
        $this->assertSame(7.0, (float) $full->items()->first()->expected_quantity);
    }

    public function test_purchase_invoice_calculates_costs_and_posts_to_inventory(): void
    {
        [$main, $branch, $product] = $this->inventoryFixtures();
        $supplier = Supplier::query()->create(['name' => 'المورد الأول']);
        $document = PurchaseDocument::query()->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $main->id,
            'type' => PurchaseDocument::TYPE_INVOICE,
            'document_date' => today(),
            'shipping_cost' => 15,
            'customs_cost' => 5,
        ]);
        $document->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_cost' => 100,
            'discount_amount' => 10,
            'tax_rate' => 15,
        ]);
        $document->expenses()->create([
            'category' => 'handling',
            'description' => 'مناولة',
            'amount' => 20,
            'expense_date' => today(),
        ]);
        $document->recalculateTotals();

        $this->assertSame(190.0, (float) $document->fresh()->subtotal);
        $this->assertSame(28.5, (float) $document->fresh()->tax_total);
        $this->assertSame(258.5, (float) $document->fresh()->grand_total);

        app(InventoryService::class)->postPurchase($document->fresh('items'));

        $this->assertSame(2.0, $this->stock($main, $product));
        $this->assertSame(PurchaseDocument::STATUS_POSTED, $document->fresh()->status);
        $this->assertSame(100.0, (float) $product->fresh()->average_cost);
    }

    private function inventoryFixtures(): array
    {
        $main = Warehouse::query()->create(['name' => 'الرئيسي', 'code' => 'MAIN']);
        $branch = Warehouse::query()->create(['name' => 'الفرع', 'code' => 'BRANCH']);
        $product = Product::query()->create([
            'name' => 'منتج مخزني',
            'slug' => 'inventory-product',
            'selling_price' => 50,
        ]);

        return [$main, $branch, $product];
    }

    private function stock(Warehouse $warehouse, Product $product): float
    {
        return (float) WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->value('quantity');
    }
}
