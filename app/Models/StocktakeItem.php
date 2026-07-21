<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StocktakeItem extends Model
{
    protected $fillable = [
        'stocktake_id',
        'product_id',
        'product_variant_id',
        'expected_quantity',
        'counted_quantity',
        'difference_quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expected_quantity' => 'decimal:3',
            'counted_quantity' => 'decimal:3',
            'difference_quantity' => 'decimal:3',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (StocktakeItem $item): void {
            $item->difference_quantity = $item->counted_quantity === null
                ? 0
                : (float) $item->counted_quantity - (float) $item->expected_quantity;
        });
    }

    public function stocktake(): BelongsTo
    {
        return $this->belongsTo(Stocktake::class);
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
