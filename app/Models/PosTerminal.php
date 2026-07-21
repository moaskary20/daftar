<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosTerminal extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'warehouse_id', 'treasury_id', 'name', 'code', 'receipt_size',
        'printer_name', 'kitchen_printer_name', 'cash_drawer_enabled',
        'customer_display_enabled', 'scale_enabled', 'offline_enabled',
        'is_active', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'cash_drawer_enabled' => 'boolean', 'customer_display_enabled' => 'boolean',
            'scale_enabled' => 'boolean', 'offline_enabled' => 'boolean',
            'is_active' => 'boolean', 'settings' => 'array',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }
}
