<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class JournalEntry extends Model
{
    use LogsModelActivity;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'created_by', 'posted_by', 'number', 'entry_date', 'entry_type', 'status',
        'source_type', 'source_id', 'reference', 'description', 'total_debit',
        'total_credit', 'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'total_debit' => 'decimal:4',
            'total_credit' => 'decimal:4',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (JournalEntry $entry): void {
            $entry->number ?: $entry->number = 'JV-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
            $entry->created_by ??= auth()->id();
            $entry->status ??= self::STATUS_DRAFT;
            $entry->entry_type ??= 'manual';
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_POSTED => 'مرحّل',
            self::STATUS_REVERSED => 'معكوس',
        ];
    }
}
