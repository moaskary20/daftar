<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseDocument;
use App\Models\SalesDocument;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesPurchaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_quotation_to_order_delivery_invoice_and_return_flow(): void
    {
        [$warehouse, $product] = $this->fixtures();
        $customer = Customer::query()->create(['name' => 'عميل دورة', 'credit_limit' => 0]);

        $quotation = $this->salesDocument($customer, $warehouse, $product, SalesDocument::TYPE_QUOTATION, 4, 50);
        app(SalesService::class)->approve($quotation->fresh());

        $order = app(SalesService::class)->convertToOrder($quotation->fresh(['items']));
        $this->assertSame(SalesDocument::TYPE_ORDER, $order->type);
        $this->assertSame(SalesDocument::STATUS_DRAFT, $order->status);
        $this->assertSame($quotation->id, $order->source_document_id);

        app(SalesService::class)->approve($order->fresh());
        $delivery = app(SalesService::class)->createDelivery($order->fresh(['items']));
        $delivery->items()->first()->update(['quantity' => 2]);
        app(SalesService::class)->completeDelivery($delivery->fresh(['items.documentItem.product', 'document.items']));

        $this->assertSame(8.0, $this->stock($warehouse, $product));
        $this->assertSame(SalesDocument::STATUS_PARTIAL, $order->fresh()->status);

        $invoice = app(SalesService::class)->convertToInvoice($order->fresh(['items']));
        $this->assertSame(2.0, (float) $invoice->items()->first()->delivered_quantity);

        app(SalesService::class)->post($invoice->fresh(['items', 'customer', 'sourceDocument']));

        $this->assertSame(6.0, $this->stock($warehouse, $product));
        $this->assertSame(SalesDocument::STATUS_POSTED, $invoice->fresh()->status);
        $this->assertSame(4.0, (float) $invoice->items()->first()->delivered_quantity);

        $return = app(SalesService::class)->createReturn($invoice->fresh(['items']));
        $return->items()->first()->update(['quantity' => 1]);
        $return->recalculateTotals();
        app(SalesService::class)->post($return->fresh(['items.product', 'customer', 'sourceDocument.items', 'sourceDocument']));

        $this->assertSame(7.0, $this->stock($warehouse, $product));
        $this->assertSame(SalesDocument::STATUS_POSTED, $return->fresh()->status);
    }

    public function test_purchase_quotation_to_order_invoice_and_return_flow(): void
    {
        [$warehouse, $product] = $this->fixtures();
        $supplier = Supplier::query()->create(['name' => 'مورد دورة']);

        $quotation = $this->purchaseDocument($supplier, $warehouse, $product, PurchaseDocument::TYPE_QUOTATION, 3, 40);
        app(PurchaseService::class)->approve($quotation->fresh());

        $order = app(PurchaseService::class)->convertToOrder($quotation->fresh(['items', 'expenses']));
        $this->assertSame(PurchaseDocument::TYPE_ORDER, $order->type);

        $invoice = app(PurchaseService::class)->convertToInvoice($order->fresh(['items', 'expenses']));
        $this->assertSame(PurchaseDocument::STATUS_APPROVED, $order->fresh()->status);

        app(PurchaseService::class)->post($invoice->fresh(['items.product']));

        $this->assertSame(13.0, $this->stock($warehouse, $product));
        $this->assertSame(PurchaseDocument::STATUS_POSTED, $invoice->fresh()->status);
        $this->assertSame(120.0, (float) $supplier->fresh()->current_balance);

        $return = app(PurchaseService::class)->createReturn($invoice->fresh(['items', 'expenses']));
        $return->items()->first()->update(['quantity' => 1]);
        $return->recalculateTotals();
        app(PurchaseService::class)->post($return->fresh(['items.product', 'sourceDocument.items']));

        $this->assertSame(12.0, $this->stock($warehouse, $product));
        $this->assertSame(80.0, (float) $supplier->fresh()->current_balance);
    }

    private function fixtures(): array
    {
        $warehouse = Warehouse::query()->create(['name' => 'المخزن', 'code' => 'WH1']);
        $product = Product::query()->create([
            'name' => 'منتج الدورة',
            'slug' => 'workflow-product',
            'selling_price' => 50,
            'average_cost' => 40,
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
            'tax_rate' => 0,
        ]);
        $document->recalculateTotals();

        return $document;
    }

    private function purchaseDocument(
        Supplier $supplier,
        Warehouse $warehouse,
        Product $product,
        string $type,
        float $quantity,
        float $cost,
    ): PurchaseDocument {
        $document = PurchaseDocument::query()->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'type' => $type,
            'document_date' => today(),
        ]);
        $document->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_cost' => $cost,
            'tax_rate' => 0,
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
