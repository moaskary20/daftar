<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'product_id', 'category_id', 'name', 'discount_type', 'value',
        'minimum_quantity', 'starts_at', 'ends_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4', 'minimum_quantity' => 'decimal:3',
            'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function isActiveFor(Product $product, float $quantity): bool
    {
        return $this->is_active
            && $quantity >= (float) $this->minimum_quantity
            && (! $this->product_id || $this->product_id === $product->id)
            && (! $this->category_id || $this->category_id === $product->category_id)
            && (! $this->starts_at || $this->starts_at->isPast())
            && (! $this->ends_at || $this->ends_at->isFuture());
    }
}
