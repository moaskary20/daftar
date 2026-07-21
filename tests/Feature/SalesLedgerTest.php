<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Product;
use App\Models\PurchaseDocument;
use App\Models\SalesDelivery;
use App\Models\SalesDocument;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use App\Services\LedgerService;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SalesLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_posts_stock_and_customer_debt_then_accepts_payment(): void
    {
        [$warehouse, $product] = $this->inventoryFixtures();
        $customer = Customer::query()->create([
            'name' => 'عميل آجل',
            'opening_balance' => 100,
            'credit_limit' => 1000,
        ]);
        $document = $this->salesDocument($customer, $warehouse, $product, SalesDocument::TYPE_INVOICE, 2, 100);

        app(SalesService::class)->post($document->fresh(['items', 'customer', 'sourceDocument']));

        $this->assertSame(8.0, $this->stock($warehouse, $product));
        $this->assertSame(300.0, (float) $customer->fresh()->current_balance);
        $this->assertSame(SalesDocument::STATUS_POSTED, $document->fresh()->status);
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => $document->getMorphClass(),
            'source_id' => $document->id,
            'status' => 'posted',
            'total_debit' => 200,
            'total_credit' => 200,
        ]);
        $this->assertDatabaseHas('customer_transactions', [
            'customer_id' => $customer->id,
            'type' => CustomerTransaction::TYPE_INVOICE,
            'debit' => 200,
        ]);

        app(LedgerService::class)->customerEntry(
            $customer,
            CustomerTransaction::TYPE_PAYMENT,
            credit: 75,
            paymentMethod: 'cash',
        );

        $this->assertSame(225.0, (float) $customer->fresh()->current_balance);
    }

    public function test_customer_credit_limit_prevents_invoice_posting(): void
    {
        [$warehouse, $product] = $this->inventoryFixtures();
        $customer = Customer::query()->create(['name' => 'عميل محدود', 'credit_limit' => 150]);
        $document = $this->salesDocument($customer, $warehouse, $product, SalesDocument::TYPE_INVOICE, 2, 100);

        $this->expectException(ValidationException::class);

        app(SalesService::class)->post($document->fresh(['items', 'customer', 'sourceDocument']));
    }

    public function test_partial_then_full_delivery_updates_order_and_stock(): void
    {
        [$warehouse, $product] = $this->inventoryFixtures();
        $customer = Customer::query()->create(['name' => 'عميل تسليم']);
        $order = $this->salesDocument($customer, $warehouse, $product, SalesDocument::TYPE_ORDER, 5, 20);
        $orderItem = $order->items()->first();

        $partial = SalesDelivery::query()->create([
            'sales_document_id' => $order->id,
            'warehouse_id' => $warehouse->id,
            'delivery_date' => today(),
        ]);
        $partial->items()->create(['sales_document_item_id' => $orderItem->id, 'quantity' => 2]);
        app(SalesService::class)->completeDelivery($partial->fresh(['items.documentItem.product', 'document.items']));

        $this->assertSame(8.0, $this->stock($warehouse, $product));
        $this->assertSame(SalesDocument::STATUS_PARTIAL, $order->fresh()->status);

        $full = SalesDelivery::query()->create([
            'sales_document_id' => $order->id,
            'warehouse_id' => $warehouse->id,
            'delivery_date' => today(),
        ]);
        $full->items()->create(['sales_document_item_id' => $orderItem->id, 'quantity' => 3]);
        app(SalesService::class)->completeDelivery($full->fresh(['items.documentItem.product', 'document.items']));

        $this->assertSame(5.0, $this->stock($warehouse, $product));
        $this->assertSame(SalesDocument::STATUS_DELIVERED, $order->fresh()->status);
        $this->assertSame(5.0, (float) $orderItem->fresh()->delivered_quantity);
    }

    public function test_purchase_invoice_and_supplier_payment_update_supplier_statement(): void
    {
        [$warehouse, $product] = $this->inventoryFixtures();
        $supplier = Supplier::query()->create(['name' => 'مورد حساب', 'opening_balance' => 50]);
        $purchase = PurchaseDocument::query()->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'type' => PurchaseDocument::TYPE_INVOICE,
            'document_date' => today(),
        ]);
        $purchase->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_cost' => 100,
        ]);
        $purchase->recalculateTotals();

        app(InventoryService::class)->postPurchase($purchase->fresh('items'));
        app(LedgerService::class)->supplierEntry(
            $supplier,
            SupplierTransaction::TYPE_CHECK,
            debit: 40,
            paymentMethod: 'check',
            check: [
                'check_number' => 'CH-100',
                'check_due_date' => today()->addMonth(),
                'bank_name' => 'البنك',
                'check_status' => 'pending',
            ],
        );

        $this->assertSame(110.0, (float) $supplier->fresh()->current_balance);
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => $purchase->getMorphClass(),
            'source_id' => $purchase->id,
            'status' => 'posted',
        ]);
        $this->assertDatabaseHas('supplier_transactions', [
            'supplier_id' => $supplier->id,
            'type' => SupplierTransaction::TYPE_CHECK,
            'check_number' => 'CH-100',
        ]);
    }

    private function inventoryFixtures(): array
    {
        $warehouse = Warehouse::query()->create(['name' => 'الرئيسي', 'code' => 'MAIN']);
        $product = Product::query()->create([
            'name' => 'منتج مبيعات',
            'slug' => 'sales-product',
            'selling_price' => 100,
        ]);
        app(InventoryService::class)->adjust($warehouse, $product, 10, StockMovement::TYPE_RECEIPT);

        return [$warehouse, $product];
    }

    private function salesDocument(
        Customer $customer,
        Warehouse $warehouse,
        Product $product,
        string $type,
        float $quantity,
        float $price,
    ): SalesDocument {
        $document = SalesDocument::query()->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'type' => $type,
            'document_date' => today(),
        ]);
        $document->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $price,
        ]);
        $document->recalculateTotals();

        return $document;
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
