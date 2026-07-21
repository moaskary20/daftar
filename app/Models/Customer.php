<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
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
        'is_active',
        'notes',
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
        static::creating(function (Customer $customer): void {
            $customer->current_balance = $customer->opening_balance ?? 0;
        });

        static::created(function (Customer $customer): void {
            if ((float) $customer->opening_balance === 0.0) {
                return;
            }

            $customer->transactions()->create([
                'number' => 'C-OPEN-'.$customer->id,
                'type' => CustomerTransaction::TYPE_OPENING,
                'debit' => max((float) $customer->opening_balance, 0),
                'credit' => abs(min((float) $customer->opening_balance, 0)),
                'balance_after' => $customer->opening_balance,
                'transaction_date' => today(),
                'notes' => 'رصيد افتتاحي',
            ]);
        });
    }

    public function salesDocuments(): HasMany
    {
        return $this->hasMany(SalesDocument::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerTransaction::class);
    }

    public function loyaltyAccount(): HasOne
    {
        return $this->hasOne(LoyaltyAccount::class);
    }

    public function getAvailableCreditAttribute(): float
    {
        if ((float) $this->credit_limit <= 0) {
            return INF;
        }

        return max(0, (float) $this->credit_limit - (float) $this->current_balance);
    }
}
