<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Voucher extends Model
{
    use LogsModelActivity;

    public const TYPE_RECEIPT = 'receipt';

    public const TYPE_PAYMENT = 'payment';

    protected $fillable = [
        'journal_entry_id', 'bank_check_id', 'created_by', 'party_type', 'party_id',
        'number', 'type', 'fund_type', 'fund_id', 'amount', 'voucher_date',
        'payment_method', 'status', 'description', 'posted_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:4', 'voucher_date' => 'date', 'posted_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Voucher $voucher): void {
            $voucher->number ?: $voucher->number = ($voucher->type === self::TYPE_RECEIPT ? 'RV-' : 'PV-').now()->format('ymd').'-'.Str::upper(Str::random(6));
            $voucher->created_by ??= auth()->id();
            $voucher->status ??= 'draft';
        });
    }

    public function party(): MorphTo
    {
        return $this->morphTo();
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function check(): BelongsTo
    {
        return $this->belongsTo(BankCheck::class, 'bank_check_id');
    }

    public static function typeLabels(): array
    {
        return [self::TYPE_RECEIPT => 'سند قبض', self::TYPE_PAYMENT => 'سند صرف'];
    }
}
