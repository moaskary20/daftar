<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PurchaseDocument extends Model
{
    use LogsModelActivity;

    public const TYPE_QUOTATION = 'quotation';

    public const TYPE_ORDER = 'order';

    public const TYPE_INVOICE = 'invoice';

    public const TYPE_RETURN = 'return';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_POSTED = 'posted';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'supplier_id',
        'warehouse_id',
        'source_document_id',
        'created_by',
        'number',
        'type',
        'status',
        'document_date',
        'expected_date',
        'supplier_reference',
        'currency',
        'subtotal',
        'discount_total',
        'tax_total',
        'shipping_cost',
        'customs_cost',
        'expense_total',
        'grand_total',
        'posted_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'expected_date' => 'date',
            'subtotal' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'shipping_cost' => 'decimal:4',
            'customs_cost' => 'decimal:4',
            'expense_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PurchaseDocument $document): void {
            $prefix = match ($document->type) {
                self::TYPE_QUOTATION => 'RFQ',
                self::TYPE_ORDER => 'PO',
                self::TYPE_INVOICE => 'PINV',
                self::TYPE_RETURN => 'PRET',
                default => 'PUR',
            };

            $document->number ?: $document->number = $prefix.'-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
            $document->created_by ??= auth()->id();
            $document->status ??= self::STATUS_DRAFT;
            $document->currency ??= 'EGP';
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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
        return $this->hasMany(PurchaseDocumentItem::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(PurchaseExpense::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('line_total');
        $discount = (float) $this->items()->sum('discount_amount');
        $tax = (float) $this->items()->sum('tax_amount');
        $expenses = (float) $this->expenses()->sum('amount');

        $this->forceFill([
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'tax_total' => $tax,
            'expense_total' => $expenses,
            'grand_total' => $subtotal + $tax + (float) $this->shipping_cost + (float) $this->customs_cost + $expenses,
        ])->saveQuietly();
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_QUOTATION => 'عرض سعر',
            self::TYPE_ORDER => 'أمر شراء',
            self::TYPE_INVOICE => 'فاتورة شراء',
            self::TYPE_RETURN => 'مرتجع شراء',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_APPROVED => 'معتمد',
            self::STATUS_POSTED => 'مرحّل للمخزون',
            self::STATUS_CANCELLED => 'ملغي',
        ];
    }
}
