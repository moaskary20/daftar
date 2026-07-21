<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Stocktake extends Model
{
    use LogsModelActivity;

    public const TYPE_PARTIAL = 'partial';

    public const TYPE_FULL = 'full';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_COUNTING = 'counting';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'warehouse_id',
        'created_by',
        'number',
        'type',
        'status',
        'stocktake_date',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'stocktake_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Stocktake $stocktake): void {
            $stocktake->number ?: $stocktake->number = 'CNT-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
            $stocktake->created_by ??= auth()->id();
            $stocktake->type ??= self::TYPE_PARTIAL;
            $stocktake->status ??= self::STATUS_DRAFT;
        });
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StocktakeItem::class);
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_PARTIAL => 'جرد جزئي',
            self::TYPE_FULL => 'جرد كامل',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_COUNTING => 'قيد العد',
            self::STATUS_COMPLETED => 'مكتمل',
            self::STATUS_CANCELLED => 'ملغي',
        ];
    }
}
