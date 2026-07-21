<?php

namespace App\Services;

use App\Models\CustomerTransaction;
use App\Models\SalesDelivery;
use App\Models\SalesDocument;
use App\Models\SalesDocumentItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesService
{
    public function approve(SalesDocument $document): void
    {
        $this->ensureNotLocked($document);

        if ($document->status !== SalesDocument::STATUS_DRAFT) {
            throw ValidationException::withMessages(['status' => 'الاعتماد متاح للمستندات في حالة المسودة فقط.']);
        }

        if (! $document->items()->exists()) {
            throw ValidationException::withMessages(['items' => 'أضف صنفاً واحداً على الأقل قبل الاعتماد.']);
        }

        $document->update(['status' => SalesDocument::STATUS_APPROVED]);
    }

    public function cancel(SalesDocument $document): void
    {
        if (in_array($document->status, [SalesDocument::STATUS_POSTED, SalesDocument::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages(['status' => 'لا يمكن إلغاء مستند مرحّل أو ملغي مسبقاً.']);
        }

        if (in_array($document->status, [SalesDocument::STATUS_PARTIAL, SalesDocument::STATUS_DELIVERED], true)) {
            throw ValidationException::withMessages(['status' => 'لا يمكن إلغاء أمر بيع تم تسليم أصناف منه. أنشئ مرتجعاً بدلاً من ذلك.']);
        }

        $document->update(['status' => SalesDocument::STATUS_CANCELLED]);
    }

    public function convertToOrder(SalesDocument $quotation): SalesDocument
    {
        if ($quotation->type !== SalesDocument::TYPE_QUOTATION) {
            throw ValidationException::withMessages(['type' => 'التحويل إلى أمر بيع متاح لعروض الأسعار فقط.']);
        }

        if (! in_array($quotation->status, [SalesDocument::STATUS_DRAFT, SalesDocument::STATUS_APPROVED], true)) {
            throw ValidationException::withMessages(['status' => 'لا يمكن تحويل عرض السعر في حالته الحالية.']);
        }

        return $this->cloneDocument($quotation, SalesDocument::TYPE_ORDER, SalesDocument::STATUS_DRAFT);
    }

    public function convertToInvoice(SalesDocument $order): SalesDocument
    {
        if ($order->type !== SalesDocument::TYPE_ORDER) {
            throw ValidationException::withMessages(['type' => 'التحويل إلى فاتورة متاح لأوامر البيع فقط.']);
        }

        if (! in_array($order->status, [
            SalesDocument::STATUS_APPROVED,
            SalesDocument::STATUS_PARTIAL,
            SalesDocument::STATUS_DELIVERED,
        ], true)) {
            throw ValidationException::withMessages(['status' => 'اعتمد أمر البيع أو أكمل التسليم قبل إنشاء الفاتورة.']);
        }

        return $this->cloneDocument($order, SalesDocument::TYPE_INVOICE, SalesDocument::STATUS_DRAFT, copyDelivered: true);
    }

    public function createReturn(SalesDocument $invoice): SalesDocument
    {
        if ($invoice->type !== SalesDocument::TYPE_INVOICE) {
            throw ValidationException::withMessages(['type' => 'مرتجع المبيعات يُنشأ من فاتورة مبيعات فقط.']);
        }

        if ($invoice->status !== SalesDocument::STATUS_POSTED) {
            throw ValidationException::withMessages(['status' => 'يجب ترحيل الفاتورة أولاً قبل إنشاء المرتجع.']);
        }

        return $this->cloneDocument($invoice, SalesDocument::TYPE_RETURN, SalesDocument::STATUS_DRAFT);
    }

    public function createDelivery(SalesDocument $order): SalesDelivery
    {
        if ($order->type !== SalesDocument::TYPE_ORDER) {
            throw ValidationException::withMessages(['type' => 'التسليم متاح لأوامر البيع فقط.']);
        }

        if ($order->status === SalesDocument::STATUS_DRAFT) {
            $this->approve($order);
            $order->refresh();
        }

        if (! in_array($order->status, [SalesDocument::STATUS_APPROVED, SalesDocument::STATUS_PARTIAL], true)) {
            throw ValidationException::withMessages(['status' => 'اعتمد أمر البيع قبل إنشاء التسليم.']);
        }

        if (! $order->warehouse_id) {
            throw ValidationException::withMessages(['warehouse_id' => 'حدد مخزن الصرف في أمر البيع.']);
        }

        $remainingItems = $order->items->filter(fn (SalesDocumentItem $item): bool => $item->remaining_quantity > 0);

        if ($remainingItems->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'لا توجد كميات متبقية للتسليم.']);
        }

        return DB::transaction(function () use ($order, $remainingItems): SalesDelivery {
            $delivery = SalesDelivery::query()->create([
                'sales_document_id' => $order->id,
                'warehouse_id' => $order->warehouse_id,
                'delivery_date' => today(),
                'status' => SalesDelivery::STATUS_DRAFT,
            ]);

            foreach ($remainingItems as $item) {
                $delivery->items()->create([
                    'sales_document_item_id' => $item->id,
                    'quantity' => $item->remaining_quantity,
                ]);
            }

            return $delivery->fresh(['items', 'document']);
        });
    }

    public function post(SalesDocument $document): void
    {
        if (! in_array($document->type, [SalesDocument::TYPE_INVOICE, SalesDocument::TYPE_RETURN], true)) {
            throw ValidationException::withMessages(['type' => 'الترحيل متاح لفواتير ومرتجعات المبيعات فقط.']);
        }

        if ($document->status === SalesDocument::STATUS_POSTED) {
            throw ValidationException::withMessages(['status' => 'تم ترحيل هذا المستند مسبقاً.']);
        }

        if ($document->status === SalesDocument::STATUS_CANCELLED) {
            throw ValidationException::withMessages(['status' => 'لا يمكن ترحيل مستند ملغي.']);
        }

        if (! $document->warehouse_id) {
            throw ValidationException::withMessages(['warehouse_id' => 'اختر المخزن قبل الترحيل.']);
        }

        if (! $document->items()->exists()) {
            throw ValidationException::withMessages(['items' => 'أضف صنفاً واحداً على الأقل قبل الترحيل.']);
        }

        $isReturn = $document->type === SalesDocument::TYPE_RETURN;

        if ($isReturn && $document->source_document_id) {
            $this->assertReturnQuantities($document);
        }

        DB::transaction(function () use ($document, $isReturn): void {
            $customer = $document->customer()->lockForUpdate()->firstOrFail();

            if (! $isReturn && (float) $customer->credit_limit > 0) {
                $newBalance = (float) $customer->current_balance + (float) $document->grand_total;

                if ($newBalance > (float) $customer->credit_limit) {
                    throw ValidationException::withMessages([
                        'customer_id' => 'الفاتورة تتجاوز الحد الائتماني للعميل.',
                    ]);
                }
            }

            foreach ($document->items as $item) {
                $quantity = abs((float) $item->quantity);
                $alreadyMoved = min($quantity, abs((float) $item->delivered_quantity));
                $pending = round($quantity - $alreadyMoved, 3);

                if ($pending > 0) {
                    $signed = $isReturn ? $pending : -$pending;

                    app(InventoryService::class)->adjust(
                        $document->warehouse_id,
                        $item->product_id,
                        $signed,
                        $isReturn ? StockMovement::TYPE_SALE_RETURN : StockMovement::TYPE_SALE,
                        $item->product_variant_id,
                        reference: $document,
                        notes: $document->number,
                    );
                }

                $item->update(['delivered_quantity' => $quantity]);
            }

            app(LedgerService::class)->customerEntry(
                $document->customer_id,
                $isReturn ? CustomerTransaction::TYPE_RETURN : CustomerTransaction::TYPE_INVOICE,
                debit: $isReturn ? 0 : (float) $document->grand_total,
                credit: $isReturn ? (float) $document->grand_total : 0,
                reference: $document,
                notes: $document->number,
            );

            app(AccountingService::class)->postSales($document);

            $document->update([
                'status' => SalesDocument::STATUS_POSTED,
                'posted_at' => now(),
                'delivered_at' => $isReturn ? $document->delivered_at : now(),
            ]);
        });
    }

    public function completeDelivery(SalesDelivery $delivery): void
    {
        if ($delivery->status !== SalesDelivery::STATUS_DRAFT) {
            throw ValidationException::withMessages(['status' => 'تم اعتماد هذا التسليم مسبقاً.']);
        }

        if ($delivery->document->type !== SalesDocument::TYPE_ORDER) {
            throw ValidationException::withMessages(['sales_document_id' => 'التسليم متاح لأوامر البيع فقط.']);
        }

        if ($delivery->document->status === SalesDocument::STATUS_DRAFT) {
            $this->approve($delivery->document);
            $delivery->load('document');
        }

        if (! in_array($delivery->document->status, [SalesDocument::STATUS_APPROVED, SalesDocument::STATUS_PARTIAL], true)) {
            throw ValidationException::withMessages(['status' => 'اعتمد أمر البيع قبل اعتماد التسليم.']);
        }

        if (! $delivery->items()->exists()) {
            throw ValidationException::withMessages(['items' => 'أضف صنفاً واحداً على الأقل.']);
        }

        DB::transaction(function () use ($delivery): void {
            foreach ($delivery->items as $deliveryItem) {
                $documentItem = $deliveryItem->documentItem()->lockForUpdate()->first();
                $quantity = abs((float) $deliveryItem->quantity);

                if ($quantity <= 0 || $quantity > $documentItem->remaining_quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => "الكمية المطلوبة تتجاوز المتبقي للصنف {$documentItem->product->name}.",
                    ]);
                }

                app(InventoryService::class)->adjust(
                    $delivery->warehouse_id,
                    $documentItem->product_id,
                    -$quantity,
                    StockMovement::TYPE_DELIVERY,
                    $documentItem->product_variant_id,
                    reference: $delivery,
                    notes: $delivery->number,
                );

                $documentItem->increment('delivered_quantity', $quantity);
            }

            $hasRemaining = $delivery->document->items()
                ->whereColumn('delivered_quantity', '<', 'quantity')
                ->exists();

            $delivery->document->update([
                'status' => $hasRemaining ? SalesDocument::STATUS_PARTIAL : SalesDocument::STATUS_DELIVERED,
                'delivered_at' => $hasRemaining ? null : now(),
            ]);

            $delivery->update([
                'status' => SalesDelivery::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        });
    }

    private function cloneDocument(
        SalesDocument $source,
        string $type,
        string $status,
        bool $copyDelivered = false,
    ): SalesDocument {
        if (! $source->items()->exists()) {
            throw ValidationException::withMessages(['items' => 'المستند المرجعي لا يحتوي على أصناف.']);
        }

        return DB::transaction(function () use ($source, $type, $status, $copyDelivered): SalesDocument {
            $document = SalesDocument::query()->create([
                'customer_id' => $source->customer_id,
                'warehouse_id' => $source->warehouse_id,
                'source_document_id' => $source->id,
                'type' => $type,
                'status' => $status,
                'document_date' => today(),
                'expected_date' => $source->expected_date,
                'customer_reference' => $source->customer_reference,
                'currency' => $source->currency ?: 'EGP',
                'invoice_discount' => $source->invoice_discount,
                'shipping_cost' => $source->shipping_cost,
                'notes' => $source->notes,
            ]);

            foreach ($source->items as $item) {
                $document->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'delivered_quantity' => $copyDelivered ? $item->delivered_quantity : 0,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'tax_rate' => $item->tax_rate,
                ]);
            }

            if ($source->status === SalesDocument::STATUS_DRAFT) {
                $source->update(['status' => SalesDocument::STATUS_APPROVED]);
            }

            $document->recalculateTotals();

            return $document->fresh(['items', 'customer', 'warehouse']);
        });
    }

    private function assertReturnQuantities(SalesDocument $return): void
    {
        $invoice = $return->sourceDocument()->with('items')->first();

        if (! $invoice || $invoice->type !== SalesDocument::TYPE_INVOICE || $invoice->status !== SalesDocument::STATUS_POSTED) {
            throw ValidationException::withMessages(['source_document_id' => 'المرتجع يجب أن يرجع لفاتورة مبيعات مرحّلة.']);
        }

        $previousReturns = SalesDocument::query()
            ->where('type', SalesDocument::TYPE_RETURN)
            ->where('source_document_id', $invoice->id)
            ->where('id', '!=', $return->id)
            ->where('status', '!=', SalesDocument::STATUS_CANCELLED)
            ->with('items')
            ->get();

        foreach ($return->items as $item) {
            $invoiceQty = (float) $invoice->items
                ->where('product_id', $item->product_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->sum('quantity');

            $returnedQty = (float) $previousReturns
                ->flatMap->items
                ->where('product_id', $item->product_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->sum('quantity');

            if (abs((float) $item->quantity) + $returnedQty > $invoiceQty + 0.0001) {
                throw ValidationException::withMessages([
                    'quantity' => "كمية المرتجع للصنف {$item->product?->name} تتجاوز الكمية المباعة.",
                ]);
            }
        }
    }

    private function ensureNotLocked(SalesDocument $document): void
    {
        if (in_array($document->status, [
            SalesDocument::STATUS_POSTED,
            SalesDocument::STATUS_CANCELLED,
            SalesDocument::STATUS_DELIVERED,
        ], true)) {
            throw ValidationException::withMessages(['status' => 'هذا المستند مقفل ولا يمكن تعديله.']);
        }
    }
}
