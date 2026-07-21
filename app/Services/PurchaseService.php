<?php

namespace App\Services;

use App\Models\PurchaseDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function approve(PurchaseDocument $document): void
    {
        if ($document->status !== PurchaseDocument::STATUS_DRAFT) {
            throw ValidationException::withMessages(['status' => 'الاعتماد متاح للمستندات في حالة المسودة فقط.']);
        }

        if (! $document->items()->exists()) {
            throw ValidationException::withMessages(['items' => 'أضف صنفاً واحداً على الأقل قبل الاعتماد.']);
        }

        $document->update(['status' => PurchaseDocument::STATUS_APPROVED]);
    }

    public function cancel(PurchaseDocument $document): void
    {
        if (in_array($document->status, [PurchaseDocument::STATUS_POSTED, PurchaseDocument::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages(['status' => 'لا يمكن إلغاء مستند مرحّل أو ملغي مسبقاً.']);
        }

        $document->update(['status' => PurchaseDocument::STATUS_CANCELLED]);
    }

    public function convertToOrder(PurchaseDocument $quotation): PurchaseDocument
    {
        if ($quotation->type !== PurchaseDocument::TYPE_QUOTATION) {
            throw ValidationException::withMessages(['type' => 'التحويل إلى أمر شراء متاح لعروض الأسعار فقط.']);
        }

        if (! in_array($quotation->status, [PurchaseDocument::STATUS_DRAFT, PurchaseDocument::STATUS_APPROVED], true)) {
            throw ValidationException::withMessages(['status' => 'لا يمكن تحويل عرض السعر في حالته الحالية.']);
        }

        return $this->cloneDocument($quotation, PurchaseDocument::TYPE_ORDER, PurchaseDocument::STATUS_DRAFT);
    }

    public function convertToInvoice(PurchaseDocument $order): PurchaseDocument
    {
        if ($order->type !== PurchaseDocument::TYPE_ORDER) {
            throw ValidationException::withMessages(['type' => 'التحويل إلى فاتورة شراء متاح لأوامر الشراء فقط.']);
        }

        if ($order->status === PurchaseDocument::STATUS_DRAFT) {
            $this->approve($order);
            $order->refresh();
        }

        if ($order->status !== PurchaseDocument::STATUS_APPROVED) {
            throw ValidationException::withMessages(['status' => 'اعتمد أمر الشراء قبل إنشاء فاتورة الشراء.']);
        }

        return $this->cloneDocument($order, PurchaseDocument::TYPE_INVOICE, PurchaseDocument::STATUS_DRAFT);
    }

    public function createReturn(PurchaseDocument $invoice): PurchaseDocument
    {
        if ($invoice->type !== PurchaseDocument::TYPE_INVOICE) {
            throw ValidationException::withMessages(['type' => 'مرتجع الشراء يُنشأ من فاتورة شراء فقط.']);
        }

        if ($invoice->status !== PurchaseDocument::STATUS_POSTED) {
            throw ValidationException::withMessages(['status' => 'يجب ترحيل فاتورة الشراء أولاً قبل إنشاء المرتجع.']);
        }

        return $this->cloneDocument($invoice, PurchaseDocument::TYPE_RETURN, PurchaseDocument::STATUS_DRAFT);
    }

    public function post(PurchaseDocument $document): void
    {
        if ($document->status === PurchaseDocument::STATUS_CANCELLED) {
            throw ValidationException::withMessages(['status' => 'لا يمكن ترحيل مستند ملغي.']);
        }

        if ($document->type === PurchaseDocument::TYPE_RETURN && $document->source_document_id) {
            $this->assertReturnQuantities($document);
        }

        app(InventoryService::class)->postPurchase($document->fresh(['items.product', 'supplier']));
    }

    private function cloneDocument(PurchaseDocument $source, string $type, string $status): PurchaseDocument
    {
        if (! $source->items()->exists()) {
            throw ValidationException::withMessages(['items' => 'المستند المرجعي لا يحتوي على أصناف.']);
        }

        return DB::transaction(function () use ($source, $type, $status): PurchaseDocument {
            $document = PurchaseDocument::query()->create([
                'supplier_id' => $source->supplier_id,
                'warehouse_id' => $source->warehouse_id,
                'source_document_id' => $source->id,
                'type' => $type,
                'status' => $status,
                'document_date' => today(),
                'expected_date' => $source->expected_date,
                'supplier_reference' => $source->supplier_reference,
                'currency' => $source->currency ?: 'EGP',
                'shipping_cost' => $source->shipping_cost,
                'customs_cost' => $source->customs_cost,
                'notes' => $source->notes,
            ]);

            foreach ($source->items as $item) {
                $document->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'description' => $item->description,
                    'batch_number' => $item->batch_number,
                    'production_date' => $item->production_date,
                    'expiry_date' => $item->expiry_date,
                    'quantity' => $item->quantity,
                    'received_quantity' => 0,
                    'unit_cost' => $item->unit_cost,
                    'discount_amount' => $item->discount_amount,
                    'tax_rate' => $item->tax_rate,
                ]);
            }

            foreach ($source->expenses as $expense) {
                $document->expenses()->create([
                    'category' => $expense->category,
                    'description' => $expense->description,
                    'amount' => $expense->amount,
                    'expense_date' => $expense->expense_date ?? today(),
                ]);
            }

            if ($source->status === PurchaseDocument::STATUS_DRAFT) {
                $source->update(['status' => PurchaseDocument::STATUS_APPROVED]);
            }

            $document->recalculateTotals();

            return $document->fresh(['items', 'expenses', 'supplier', 'warehouse']);
        });
    }

    private function assertReturnQuantities(PurchaseDocument $return): void
    {
        $invoice = $return->sourceDocument()->with('items')->first();

        if (! $invoice || $invoice->type !== PurchaseDocument::TYPE_INVOICE || $invoice->status !== PurchaseDocument::STATUS_POSTED) {
            throw ValidationException::withMessages(['source_document_id' => 'المرتجع يجب أن يرجع لفاتورة شراء مرحّلة.']);
        }

        $previousReturns = PurchaseDocument::query()
            ->where('type', PurchaseDocument::TYPE_RETURN)
            ->where('source_document_id', $invoice->id)
            ->where('id', '!=', $return->id)
            ->where('status', '!=', PurchaseDocument::STATUS_CANCELLED)
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
                    'quantity' => "كمية المرتجع للصنف {$item->product?->name} تتجاوز الكمية المشتراة.",
                ]);
            }
        }
    }
}
