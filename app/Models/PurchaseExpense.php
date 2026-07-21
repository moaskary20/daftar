<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseExpense extends Model
{
    protected $fillable = [
        'purchase_document_id',
        'category',
        'description',
        'amount',
        'expense_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'expense_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (PurchaseExpense $expense) => $expense->document?->recalculateTotals());
        static::deleted(fn (PurchaseExpense $expense) => $expense->document?->recalculateTotals());
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(PurchaseDocument::class, 'purchase_document_id');
    }
}
