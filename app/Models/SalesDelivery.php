<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SalesDelivery extends Model
{
    use LogsModelActivity;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'sales_document_id',
        'warehouse_id',
        'created_by',
        'number',
        'status',
        'delivery_date',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesDelivery $delivery): void {
            $delivery->number ?: $delivery->number = 'DLV-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
            $delivery->created_by ??= auth()->id();
            $delivery->status ??= self::STATUS_DRAFT;
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(SalesDocument::class, 'sales_document_id');
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
        return $this->hasMany(SalesDeliveryItem::class);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_COMPLETED => 'تم التسليم',
            self::STATUS_CANCELLED => 'ملغي',
        ];
    }
}
