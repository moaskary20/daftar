<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartAccount extends Model
{
    use LogsModelActivity;

    public const TYPE_ASSET = 'asset';

    public const TYPE_LIABILITY = 'liability';

    public const TYPE_EQUITY = 'equity';

    public const TYPE_REVENUE = 'revenue';

    public const TYPE_EXPENSE = 'expense';

    protected $fillable = [
        'parent_id', 'code', 'name', 'type', 'normal_balance', 'is_group',
        'allow_posting', 'is_active', 'opening_debit', 'opening_credit', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_group' => 'boolean',
            'allow_posting' => 'boolean',
            'is_active' => 'boolean',
            'opening_debit' => 'decimal:4',
            'opening_credit' => 'decimal:4',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function getCurrentBalanceAttribute(): float
    {
        $debit = (float) $this->opening_debit + (float) $this->lines()->whereHas('entry', fn ($query) => $query->where('status', JournalEntry::STATUS_POSTED))->sum('debit');
        $credit = (float) $this->opening_credit + (float) $this->lines()->whereHas('entry', fn ($query) => $query->where('status', JournalEntry::STATUS_POSTED))->sum('credit');

        return $this->normal_balance === 'debit' ? $debit - $credit : $credit - $debit;
    }

    public function getDepthAttribute(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_ASSET => 'الأصول',
            self::TYPE_LIABILITY => 'الخصوم',
            self::TYPE_EQUITY => 'رأس المال وحقوق الملكية',
            self::TYPE_REVENUE => 'الإيرادات',
            self::TYPE_EXPENSE => 'المصروفات',
        ];
    }
}
