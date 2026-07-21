<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'code', 'name', 'discount_type', 'value', 'minimum_total',
        'maximum_discount', 'usage_limit', 'usage_count', 'starts_at',
        'ends_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4', 'minimum_total' => 'decimal:4',
            'maximum_discount' => 'decimal:4', 'starts_at' => 'datetime',
            'ends_at' => 'datetime', 'is_active' => 'boolean',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SalesDocument::class);
    }

    public function isUsable(float $total): bool
    {
        return $this->is_active
            && $total >= (float) $this->minimum_total
            && (! $this->usage_limit || $this->usage_count < $this->usage_limit)
            && (! $this->starts_at || $this->starts_at->isPast())
            && (! $this->ends_at || $this->ends_at->isFuture());
    }

    public function discountFor(float $total): float
    {
        $discount = $this->discount_type === 'percentage'
            ? $total * ((float) $this->value / 100)
            : (float) $this->value;

        return min($total, $this->maximum_discount ? min($discount, (float) $this->maximum_discount) : $discount);
    }
}
