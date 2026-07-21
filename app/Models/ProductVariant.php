<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'barcode',
        'qr_code',
        'color',
        'color_hex',
        'size',
        'weight',
        'purchase_price',
        'average_cost',
        'selling_price',
        'stock_quantity',
        'attributes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:3',
            'purchase_price' => 'decimal:4',
            'average_cost' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'stock_quantity' => 'decimal:3',
            'attributes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ProductVariant $variant): void {
            $variant->sku ?: $variant->sku = 'VAR-'.Str::upper(Str::random(8));
            $variant->barcode ?: $variant->barcode = Product::generateEan13();
            $variant->qr_code ?: $variant->qr_code = 'variant:'.$variant->sku;
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }
}
