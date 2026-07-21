<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_id', 'employee_id', 'basic_salary', 'allowances', 'incentives',
        'overtime_amount', 'advances', 'penalties', 'absence_deductions',
        'other_deductions', 'net_salary',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:4',
            'allowances' => 'decimal:4',
            'incentives' => 'decimal:4',
            'overtime_amount' => 'decimal:4',
            'advances' => 'decimal:4',
            'penalties' => 'decimal:4',
            'absence_deductions' => 'decimal:4',
            'other_deductions' => 'decimal:4',
            'net_salary' => 'decimal:4',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
