<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupplierTransaction extends Model
{
    use LogsModelActivity;

    public const TYPE_OPENING = 'opening';

    public const TYPE_INVOICE = 'invoice';

    public const TYPE_RETURN = 'return';

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_CHECK = 'check';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'supplier_id',
        'created_by',
        'journal_entry_id',
        'number',
        'type',
        'debit',
        'credit',
        'balance_after',
        'reference_type',
        'reference_id',
        'transaction_date',
        'payment_method',
        'fund_type',
        'fund_id',
        'check_number',
        'check_due_date',
        'bank_name',
        'check_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:4',
            'credit' => 'decimal:4',
            'balance_after' => 'decimal:4',
            'transaction_date' => 'date',
            'check_due_date' => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public static function labels(): array
    {
        return [
            self::TYPE_OPENING => 'رصيد افتتاحي',
            self::TYPE_INVOICE => 'فاتورة شراء',
            self::TYPE_RETURN => 'مرتجع شراء',
            self::TYPE_PAYMENT => 'دفعة مورد',
            self::TYPE_CHECK => 'شيك',
            self::TYPE_ADJUSTMENT => 'تسوية حساب',
        ];
    }

    public static function checkStatusLabels(): array
    {
        return [
            'pending' => 'تحت التحصيل',
            'cleared' => 'تم الصرف',
            'bounced' => 'مرتد',
            'cancelled' => 'ملغي',
        ];
    }
}
