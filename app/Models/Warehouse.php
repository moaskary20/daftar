<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'name',
        'code',
        'manager_name',
        'phone',
        'address',
        'is_active',
        'is_default',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Warehouse $warehouse): void {
            if ($warehouse->is_default) {
                static::query()
                    ->when($warehouse->exists, fn ($query) => $query->whereKeyNot($warehouse->getKey()))
                    ->update(['is_default' => false]);
            }
        });
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
