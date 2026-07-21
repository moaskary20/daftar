<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SalesDocument extends Model
{
    use LogsModelActivity;

    public const TYPE_QUOTATION = 'quotation';

    public const TYPE_ORDER = 'order';

    public const TYPE_INVOICE = 'invoice';

    public const TYPE_RETURN = 'return';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_POSTED = 'posted';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'customer_id',
        'warehouse_id',
        'pos_session_id',
        'coupon_id',
        'source_document_id',
        'created_by',
        'number',
        'client_uuid',
        'type',
        'channel',
        'payment_type',
        'status',
        'document_date',
        'expected_date',
        'customer_reference',
        'currency',
        'subtotal',
        'discount_total',
        'invoice_discount',
        'loyalty_points_earned',
        'loyalty_points_redeemed',
        'print_count',
        'tax_total',
        'shipping_cost',
        'grand_total',
        'posted_at',
        'held_at',
        'offline_synced_at',
        'delivered_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'expected_date' => 'date',
            'subtotal' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'invoice_discount' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'shipping_cost' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'posted_at' => 'datetime',
            'held_at' => 'datetime',
            'offline_synced_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesDocument $document): void {
            $prefix = match ($document->type) {
                self::TYPE_QUOTATION => 'SQ',
                self::TYPE_ORDER => 'SO',
                self::TYPE_INVOICE => 'SINV',
                self::TYPE_RETURN => 'SRET',
                default => 'SAL',
            };

            $document->number ?: $document->number = $prefix.'-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
            $document->created_by ??= auth()->id();
            $document->status ??= self::STATUS_DRAFT;
            $document->currency ??= 'EGP';
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_document_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesDocumentItem::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(SalesDelivery::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class);
    }

    public function installmentPlan(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(InstallmentPlan::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('line_total');
        $itemDiscount = (float) $this->items()->sum('discount_amount');
        $invoiceDiscount = min((float) $this->invoice_discount, $subtotal);
        $tax = (float) $this->items()->sum('tax_amount');

        $this->forceFill([
            'subtotal' => $subtotal,
            'discount_total' => $itemDiscount + $invoiceDiscount,
            'tax_total' => $tax,
            'grand_total' => max(0, $subtotal + $tax + (float) $this->shipping_cost - $invoiceDiscount),
        ])->saveQuietly();
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_QUOTATION => 'عرض سعر',
            self::TYPE_ORDER => 'أمر بيع',
            self::TYPE_INVOICE => 'فاتورة مبيعات',
            self::TYPE_RETURN => 'مرتجع مبيعات',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_APPROVED => 'معتمد',
            self::STATUS_PARTIAL => 'تسليم جزئي',
            self::STATUS_DELIVERED => 'تسليم كامل',
            self::STATUS_POSTED => 'مرحّل',
            self::STATUS_CANCELLED => 'ملغي',
        ];
    }
}
