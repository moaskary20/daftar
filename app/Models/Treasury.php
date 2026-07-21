<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Treasury extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'chart_account_id', 'name', 'code', 'opening_balance', 'current_balance',
        'is_default', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:4',
            'current_balance' => 'decimal:4',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (Treasury $treasury) => $treasury->current_balance ??= $treasury->opening_balance ?? 0);
        static::saving(function (Treasury $treasury): void {
            if ($treasury->is_default) {
                static::query()->whereKeyNot($treasury->getKey())->update(['is_default' => false]);
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'chart_account_id');
    }
}
