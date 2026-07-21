<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPayment extends Model
{
    protected $fillable = [
        'installment_plan_id', 'sequence', 'due_date', 'amount',
        'paid_amount', 'paid_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date', 'amount' => 'decimal:4',
            'paid_amount' => 'decimal:4', 'paid_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class, 'installment_plan_id');
    }
}
