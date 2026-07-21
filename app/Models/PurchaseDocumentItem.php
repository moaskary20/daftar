<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseDocumentItem extends Model
{
    protected $fillable = [
        'purchase_document_id',
        'product_id',
        'product_variant_id',
        'description',
        'batch_number',
        'production_date',
        'expiry_date',
        'quantity',
        'received_quantity',
        'unit_cost',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'production_date' => 'date',
            'expiry_date' => 'date',
            'received_quantity' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PurchaseDocumentItem $item): void {
            $net = max(0, ((float) $item->quantity * (float) $item->unit_cost) - (float) $item->discount_amount);
            $item->tax_amount = $net * ((float) $item->tax_rate / 100);
            $item->line_total = $net;
        });

        static::saved(fn (PurchaseDocumentItem $item) => $item->document?->recalculateTotals());
        static::deleted(fn (PurchaseDocumentItem $item) => $item->document?->recalculateTotals());
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(PurchaseDocument::class, 'purchase_document_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
