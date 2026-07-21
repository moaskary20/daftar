<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PosPayment extends Model
{
    public const METHOD_CASH = 'cash';

    public const METHOD_CARD = 'card';

    public const METHOD_WALLET = 'wallet';

    public const METHOD_BANK = 'bank';

    protected $fillable = [
        'sales_document_id', 'pos_session_id', 'method', 'amount',
        'fund_type', 'fund_id', 'reference', 'status', 'paid_at', 'metadata',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:4', 'paid_at' => 'datetime', 'metadata' => 'array'];
    }

    public static function methodLabels(): array
    {
        return [
            self::METHOD_CASH => 'نقدي',
            self::METHOD_CARD => 'بطاقة / فيزا',
            self::METHOD_WALLET => 'محفظة إلكترونية',
            self::METHOD_BANK => 'تحويل بنكي',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(SalesDocument::class, 'sales_document_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function fund(): MorphTo
    {
        return $this->morphTo();
    }
}
