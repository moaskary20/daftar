<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use LogsModelActivity;
    use SoftDeletes;

    public const TYPE_SIMPLE = 'simple';

    public const TYPE_VARIABLE = 'variable';

    protected $fillable = [
        'category_id',
        'department_id',
        'brand_id',
        'unit_id',
        'primary_supplier_id',
        'name',
        'slug',
        'sku',
        'type',
        'barcode',
        'qr_code',
        'description',
        'image',
        'purchase_price',
        'average_cost',
        'selling_price',
        'stock_quantity',
        'minimum_stock',
        'weight',
        'track_stock',
        'send_to_kitchen',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:4',
            'average_cost' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'stock_quantity' => 'decimal:3',
            'minimum_stock' => 'decimal:3',
            'weight' => 'decimal:3',
            'track_stock' => 'boolean',
            'send_to_kitchen' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            $product->sku ?: $product->sku = 'PRD-'.Str::upper(Str::random(8));
            $product->barcode ?: $product->barcode = self::generateEan13();
            $product->qr_code ?: $product->qr_code = 'product:'.$product->sku;
        });
    }

    public static function generateEan13(): string
    {
        $base = '2'.str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);
        $sum = 0;

        foreach (str_split($base) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return $base.((10 - ($sum % 10)) % 10);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function primarySupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'primary_supplier_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function serials(): HasMany
    {
        return $this->hasMany(ProductSerial::class);
    }

    public function getDisplayPriceAttribute(): string
    {
        return number_format((float) $this->selling_price, 2);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        if (str_starts_with($this->image, 'images/')) {
            return asset($this->image);
        }

        return asset('storage/'.$this->image);
    }
}
