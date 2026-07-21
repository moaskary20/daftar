<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'name',
        'code',
        'contact_person',
        'phone',
        'email',
        'address',
        'tax_number',
        'opening_balance',
        'current_balance',
        'credit_limit',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:4',
            'current_balance' => 'decimal:4',
            'credit_limit' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Supplier $supplier): void {
            $supplier->current_balance = $supplier->opening_balance ?? 0;
        });

        static::created(function (Supplier $supplier): void {
            if ((float) $supplier->opening_balance === 0.0) {
                return;
            }

            $supplier->transactions()->create([
                'number' => 'S-OPEN-'.$supplier->id,
                'type' => SupplierTransaction::TYPE_OPENING,
                'debit' => abs(min((float) $supplier->opening_balance, 0)),
                'credit' => max((float) $supplier->opening_balance, 0),
                'balance_after' => $supplier->opening_balance,
                'transaction_date' => today(),
                'notes' => 'رصيد افتتاحي',
            ]);
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'primary_supplier_id');
    }

    public function purchaseDocuments(): HasMany
    {
        return $this->hasMany(PurchaseDocument::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SupplierTransaction::class);
    }
}
