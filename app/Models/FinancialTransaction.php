<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FinancialTransaction extends Model
{
    use LogsModelActivity;

    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_WITHDRAWAL = 'withdrawal';

    public const TYPE_TRANSFER = 'transfer';

    protected $fillable = [
        'journal_entry_id', 'created_by', 'number', 'type', 'source_fund_type',
        'source_fund_id', 'destination_fund_type', 'destination_fund_id', 'amount',
        'transaction_date', 'status', 'beneficiary', 'description', 'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'transaction_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FinancialTransaction $transaction): void {
            $transaction->number ?: $transaction->number = 'FT-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
            $transaction->created_by ??= auth()->id();
            $transaction->status ??= 'draft';
        });
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_DEPOSIT => 'توريد',
            self::TYPE_WITHDRAWAL => 'سحب',
            self::TYPE_TRANSFER => 'تحويل',
        ];
    }
}
