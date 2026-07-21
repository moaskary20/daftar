<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PosSession extends Model
{
    use LogsModelActivity;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'pos_terminal_id', 'user_id', 'warehouse_id', 'number', 'status',
        'opening_balance', 'expected_balance', 'closing_balance', 'difference',
        'opened_at', 'closed_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:4', 'expected_balance' => 'decimal:4',
            'closing_balance' => 'decimal:4', 'difference' => 'decimal:4',
            'opened_at' => 'datetime', 'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $session): void {
            $session->number ??= 'POS-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
            $session->opened_at ??= now();
        });
    }

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class, 'pos_terminal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SalesDocument::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class);
    }
}
