<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Expense extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'expense_category_id', 'employee_id', 'journal_entry_id', 'created_by',
        'number', 'expense_type', 'amount', 'expense_date', 'payment_fund_type',
        'payment_fund_id', 'status', 'description', 'posted_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:4', 'expense_date' => 'date', 'posted_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Expense $expense): void {
            $expense->number ?: $expense->number = 'EXP-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
            $expense->created_by ??= auth()->id();
            $expense->status ??= 'draft';
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public static function typeLabels(): array
    {
        return [
            'general' => 'مصروف عام',
            'salary' => 'مرتبات',
            'rent' => 'إيجار',
            'maintenance' => 'صيانة',
        ];
    }
}
