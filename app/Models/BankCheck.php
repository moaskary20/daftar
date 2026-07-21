<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class BankCheck extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'bank_account_id', 'journal_entry_id', 'created_by', 'party_type', 'party_id',
        'number', 'check_number', 'direction', 'bank_name', 'amount', 'issue_date',
        'due_date', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'issue_date' => 'date',
            'due_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BankCheck $check): void {
            $check->number ?: $check->number = 'CHK-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
            $check->created_by ??= auth()->id();
            $check->status ??= 'pending';
        });
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function party(): MorphTo
    {
        return $this->morphTo();
    }

    public static function statusLabels(): array
    {
        return [
            'pending' => 'تحت التحصيل',
            'collected' => 'تم التحصيل',
            'paid' => 'تم الصرف',
            'bounced' => 'مرتد',
            'cancelled' => 'ملغي',
        ];
    }
}
