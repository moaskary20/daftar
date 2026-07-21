<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentPlan extends Model
{
    protected $fillable = [
        'sales_document_id', 'customer_id', 'total_amount', 'down_payment',
        'installment_amount', 'installments_count', 'frequency',
        'first_due_date', 'status',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:4', 'down_payment' => 'decimal:4',
            'installment_amount' => 'decimal:4', 'first_due_date' => 'date',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(SalesDocument::class, 'sales_document_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class);
    }
}
