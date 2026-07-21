<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use LogsModelActivity;

    public const TYPE_RECEIPT = 'receipt';

    public const TYPE_ISSUE = 'issue';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const TYPE_STOCKTAKE = 'stocktake';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_PURCHASE_RETURN = 'purchase_return';

    public const TYPE_SALE = 'sale';

    public const TYPE_SALE_RETURN = 'sale_return';

    public const TYPE_DELIVERY = 'delivery';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'product_variant_id',
        'created_by',
        'movement_number',
        'type',
        'quantity',
        'balance_before',
        'balance_after',
        'unit_cost',
        'reference_type',
        'reference_id',
        'notes',
        'moved_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'balance_before' => 'decimal:3',
            'balance_after' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'moved_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public static function labels(): array
    {
        return [
            self::TYPE_RECEIPT => 'إضافة مخزون',
            self::TYPE_ISSUE => 'صرف مخزون',
            self::TYPE_ADJUSTMENT => 'تسوية مخزون',
            self::TYPE_TRANSFER_IN => 'تحويل وارد',
            self::TYPE_TRANSFER_OUT => 'تحويل صادر',
            self::TYPE_STOCKTAKE => 'فرق جرد',
            self::TYPE_PURCHASE => 'فاتورة شراء',
            self::TYPE_PURCHASE_RETURN => 'مرتجع شراء',
            self::TYPE_SALE => 'فاتورة مبيعات',
            self::TYPE_SALE_RETURN => 'مرتجع مبيعات',
            self::TYPE_DELIVERY => 'تسليم طلب بيع',
        ];
    }
}
