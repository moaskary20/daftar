<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesDocumentItem extends Model
{
    protected $fillable = [
        'sales_document_id',
        'product_id',
        'product_variant_id',
        'description',
        'serial_number',
        'quantity',
        'delivered_quantity',
        'unit_price',
        'price_overridden',
        'promotion_id',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'delivered_quantity' => 'decimal:3',
            'unit_price' => 'decimal:4',
            'price_overridden' => 'boolean',
            'discount_amount' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SalesDocumentItem $item): void {
            $net = max(0, ((float) $item->quantity * (float) $item->unit_price) - (float) $item->discount_amount);
            $item->tax_amount = $net * ((float) $item->tax_rate / 100);
            $item->line_total = $net;
        });

        static::saved(fn (SalesDocumentItem $item) => $item->document?->recalculateTotals());
        static::deleted(fn (SalesDocumentItem $item) => $item->document?->recalculateTotals());
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(SalesDocument::class, 'sales_document_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, (float) $this->quantity - (float) $this->delivered_quantity);
    }
}
