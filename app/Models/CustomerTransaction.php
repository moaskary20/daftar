<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerTransaction extends Model
{
    use LogsModelActivity;

    public const TYPE_OPENING = 'opening';

    public const TYPE_INVOICE = 'invoice';

    public const TYPE_RETURN = 'return';

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'customer_id',
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
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:4',
            'credit' => 'decimal:4',
            'balance_after' => 'decimal:4',
            'transaction_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
            self::TYPE_INVOICE => 'فاتورة مبيعات',
            self::TYPE_RETURN => 'مرتجع مبيعات',
            self::TYPE_PAYMENT => 'دفعة عميل',
            self::TYPE_ADJUSTMENT => 'تسوية حساب',
        ];
    }
}
