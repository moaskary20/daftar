<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use LogsModelActivity;
    use SoftDeletes;

    protected $fillable = [
        'employee_number', 'name', 'job_title', 'department_name', 'national_id',
        'fingerprint_id', 'phone', 'email', 'address', 'hire_date', 'basic_salary',
        'fixed_allowances', 'bank_name', 'iban', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'basic_salary' => 'decimal:4',
            'fixed_allowances' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(EmployeeAdjustment::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }
}
