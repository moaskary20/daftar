<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payroll extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'journal_entry_id', 'created_by', 'number', 'period_month', 'status',
        'total_earnings', 'total_deductions', 'net_total', 'payment_date',
        'payment_fund_type', 'payment_fund_id', 'posted_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_earnings' => 'decimal:4',
            'total_deductions' => 'decimal:4',
            'net_total' => 'decimal:4',
            'payment_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payroll $payroll): void {
            $payroll->number ?: $payroll->number = 'PAY-'.Str::replace('-', '', $payroll->period_month).'-'.Str::upper(Str::random(4));
            $payroll->created_by ??= auth()->id();
            $payroll->status ??= 'draft';
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
