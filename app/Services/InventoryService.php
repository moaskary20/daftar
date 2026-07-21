<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseDocument;
use App\Models\StockMovement;
use App\Models\Stocktake;
use App\Models\StockTransfer;
use App\Models\SupplierTransaction;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function adjust(
        Warehouse|int $warehouse,
        Product|int $product,
        float $quantity,
        string $type,
        ProductVariant|int|null $variant = null,
        ?float $unitCost = null,
        ?Model $reference = null,
        ?string $notes = null,
    ): StockMovement {
        $warehouseId = $warehouse instanceof Warehouse ? $warehouse->getKey() : $warehouse;
        $productModel = $product instanceof Product ? $product : Product::query()->findOrFail($product);
        $variantId = $variant instanceof ProductVariant ? $variant->getKey() : $variant;

        return DB::transaction(function () use (
            $warehouseId,
            $productModel,
            $variantId,
            $quantity,
            $type,
            $unitCost,
            $reference,
            $notes,
        ): StockMovement {
            $stock = WarehouseStock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productModel->getKey())
                ->where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = WarehouseStock::query()->create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productModel->getKey(),
                    'product_variant_id' => $variantId,
                    'quantity' => 0,
                    'reorder_level' => (float) ($productModel->minimum_stock ?? 0),
                ]);
            }

            $before = (float) $stock->quantity;
            $after = round($before + $quantity, 3);

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "الرصيد غير كافٍ. المتاح حالياً: {$before}",
                ]);
            }

            $stock->update(['quantity' => $after]);

            $movement = StockMovement::query()->create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productModel->getKey(),
                'product_variant_id' => $variantId,
                'created_by' => auth()->id(),
                'movement_number' => 'MOV-'.now()->format('ymdHis').'-'.random_int(100, 999),
                'type' => $type,
                'quantity' => $quantity,
                'balance_before' => $before,
                'balance_after' => $after,
                'unit_cost' => $unitCost,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'notes' => $notes,
                'moved_at' => now(),
            ]);

            if ($quantity > 0 && $unitCost !== null && in_array($type, [StockMovement::TYPE_PURCHASE, StockMovement::TYPE_RECEIPT], true)) {
                $oldValue = (float) $productModel->average_cost * max($before, 0);
                $newAverage = ($oldValue + ($quantity * $unitCost)) / max($after, 1);
                $productModel->update(['average_cost' => round($newAverage, 4)]);
            }

            $this->syncAggregatedStock($productModel, $variantId);

            return $movement;
        });
    }

    public function completeTransfer(StockTransfer $transfer): void
    {
        if ($transfer->status !== StockTransfer::STATUS_DRAFT) {
            throw ValidationException::withMessages(['status' => 'لا يمكن اعتماد تحويل غير مسودة.']);
        }

        if ($transfer->from_warehouse_id === $transfer->to_warehouse_id) {
            throw ValidationException::withMessages(['to_warehouse_id' => 'يجب اختيار مخزن مختلف.']);
        }

        if (! $transfer->items()->exists()) {
            throw ValidationException::withMessages(['items' => 'أضف صنفاً واحداً على الأقل للتحويل.']);
        }

        DB::transaction(function () use ($transfer): void {
            foreach ($transfer->items as $item) {
                $this->adjust(
                    $transfer->from_warehouse_id,
                    $item->product_id,
                    -abs((float) $item->quantity),
                    StockMovement::TYPE_TRANSFER_OUT,
                    $item->product_variant_id,
                    reference: $transfer,
                    notes: 'تحويل إلى '.$transfer->toWarehouse->name,
                );

                $this->adjust(
                    $transfer->to_warehouse_id,
                    $item->product_id,
                    abs((float) $item->quantity),
                    StockMovement::TYPE_TRANSFER_IN,
                    $item->product_variant_id,
                    reference: $transfer,
                    notes: 'تحويل من '.$transfer->fromWarehouse->name,
                );
            }

            $transfer->update([
                'status' => StockTransfer::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        });
    }

    public function prepareStocktake(Stocktake $stocktake): void
    {
        if ($stocktake->status !== Stocktake::STATUS_DRAFT) {
            return;
        }

        DB::transaction(function () use ($stocktake): void {
            if ($stocktake->type === Stocktake::TYPE_FULL) {
                $stocktake->items()->delete();

                WarehouseStock::query()
                    ->where('warehouse_id', $stocktake->warehouse_id)
                    ->each(function (WarehouseStock $stock) use ($stocktake): void {
                        $stocktake->items()->create([
                            'product_id' => $stock->product_id,
                            'product_variant_id' => $stock->product_variant_id,
                            'expected_quantity' => $stock->quantity,
                        ]);
                    });
            } else {
                foreach ($stocktake->items as $item) {
                    $expected = WarehouseStock::query()
                        ->where('warehouse_id', $stocktake->warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->where('product_variant_id', $item->product_variant_id)
                        ->value('quantity') ?? 0;

                    $item->update(['expected_quantity' => $expected]);
                }
            }

            $stocktake->update(['status' => Stocktake::STATUS_COUNTING]);
        });
    }

    public function completeStocktake(Stocktake $stocktake): void
    {
        if ($stocktake->status !== Stocktake::STATUS_COUNTING) {
            throw ValidationException::withMessages(['status' => 'ابدأ الجرد أولاً قبل اعتماده.']);
        }

        if ($stocktake->items()->whereNull('counted_quantity')->exists()) {
            throw ValidationException::withMessages(['items' => 'يجب إدخال الكمية الفعلية لكل الأصناف.']);
        }

        DB::transaction(function () use ($stocktake): void {
            foreach ($stocktake->items as $item) {
                $difference = (float) $item->counted_quantity - (float) $item->expected_quantity;

                if ($difference !== 0.0) {
                    $this->adjust(
                        $stocktake->warehouse_id,
                        $item->product_id,
                        $difference,
                        StockMovement::TYPE_STOCKTAKE,
                        $item->product_variant_id,
                        reference: $stocktake,
                        notes: 'تسوية فرق الجرد '.$stocktake->number,
                    );
                }

                $item->update(['difference_quantity' => $difference]);
            }

            $stocktake->update([
                'status' => Stocktake::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        });
    }

    public function postPurchase(PurchaseDocument $document): void
    {
        if (! in_array($document->type, [PurchaseDocument::TYPE_INVOICE, PurchaseDocument::TYPE_RETURN], true)) {
            throw ValidationException::withMessages(['type' => 'الترحيل للمخزون متاح للفواتير والمرتجعات فقط.']);
        }

        if ($document->status === PurchaseDocument::STATUS_POSTED) {
            throw ValidationException::withMessages(['status' => 'تم ترحيل هذا المستند مسبقاً.']);
        }

        if ($document->status === PurchaseDocument::STATUS_CANCELLED) {
            throw ValidationException::withMessages(['status' => 'لا يمكن ترحيل مستند ملغي.']);
        }

        if (! $document->warehouse_id) {
            throw ValidationException::withMessages(['warehouse_id' => 'اختر المخزن قبل الترحيل.']);
        }

        if (! $document->items()->exists()) {
            throw ValidationException::withMessages(['items' => 'أضف صنفاً واحداً على الأقل قبل الترحيل.']);
        }

        DB::transaction(function () use ($document): void {
            $isReturn = $document->type === PurchaseDocument::TYPE_RETURN;

            foreach ($document->items as $item) {
                $quantity = abs((float) $item->quantity) * ($isReturn ? -1 : 1);

                $this->adjust(
                    $document->warehouse_id,
                    $item->product_id,
                    $quantity,
                    $isReturn ? StockMovement::TYPE_PURCHASE_RETURN : StockMovement::TYPE_PURCHASE,
                    $item->product_variant_id,
                    (float) $item->unit_cost,
                    $document,
                    $document->number,
                );

                $item->update(['received_quantity' => abs((float) $item->quantity)]);

                if ($item->batch_number || $item->expiry_date) {
                    $batchNumber = $item->batch_number ?: 'BATCH-'.$item->id;
                    $batch = InventoryBatch::query()
                        ->where('warehouse_id', $document->warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->where('product_variant_id', $item->product_variant_id)
                        ->where('batch_number', $batchNumber)
                        ->lockForUpdate()
                        ->first();

                    if (! $batch && $isReturn) {
                        throw ValidationException::withMessages([
                            'batch_number' => "دفعة الصنف {$item->product->name} غير موجودة.",
                        ]);
                    }

                    $batch ??= InventoryBatch::query()->create([
                        'warehouse_id' => $document->warehouse_id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'purchase_document_item_id' => $item->id,
                        'batch_number' => $batchNumber,
                        'production_date' => $item->production_date,
                        'expiry_date' => $item->expiry_date,
                        'unit_cost' => $item->unit_cost,
                    ]);

                    if ($isReturn && (float) $batch->quantity < abs($quantity)) {
                        throw ValidationException::withMessages([
                            'quantity' => "رصيد الدفعة {$batchNumber} غير كافٍ.",
                        ]);
                    }

                    $batch->increment('quantity', $quantity);
                }
            }

            app(LedgerService::class)->supplierEntry(
                $document->supplier_id,
                $isReturn ? SupplierTransaction::TYPE_RETURN : SupplierTransaction::TYPE_INVOICE,
                debit: $isReturn ? (float) $document->grand_total : 0,
                credit: $isReturn ? 0 : (float) $document->grand_total,
                reference: $document,
                notes: $document->number,
            );

            app(AccountingService::class)->postPurchase($document);

            $document->update([
                'status' => PurchaseDocument::STATUS_POSTED,
                'posted_at' => now(),
            ]);
        });
    }

    private function syncAggregatedStock(Product $product, ?int $variantId): void
    {
        if ($variantId) {
            ProductVariant::query()
                ->whereKey($variantId)
                ->update([
                    'stock_quantity' => WarehouseStock::query()
                        ->where('product_variant_id', $variantId)
                        ->sum('quantity'),
                ]);
        }

        $product->update([
            'stock_quantity' => WarehouseStock::query()
                ->where('product_id', $product->getKey())
                ->sum('quantity'),
        ]);
    }
}
