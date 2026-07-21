<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAdjustment extends Model
{
    use LogsModelActivity;

    public const TYPE_ADVANCE = 'advance';

    public const TYPE_PENALTY = 'penalty';

    public const TYPE_INCENTIVE = 'incentive';

    protected $fillable = [
        'employee_id', 'type', 'amount', 'adjustment_date', 'installments',
        'settled_amount', 'status', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'settled_amount' => 'decimal:4',
            'adjustment_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_ADVANCE => 'سلفة',
            self::TYPE_PENALTY => 'جزاء',
            self::TYPE_INCENTIVE => 'حافز',
        ];
    }
}
