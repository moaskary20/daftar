<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'employee_id', 'attendance_date', 'check_in', 'check_out', 'status',
        'late_minutes', 'overtime_hours', 'source', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'late_minutes' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public static function statusLabels(): array
    {
        return [
            'present' => 'حاضر',
            'absent' => 'غائب',
            'leave' => 'إجازة',
            'holiday' => 'عطلة',
        ];
    }
}
