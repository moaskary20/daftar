<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesDeliveryItem extends Model
{
    protected $fillable = [
        'sales_delivery_id',
        'sales_document_item_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(SalesDelivery::class, 'sales_delivery_id');
    }

    public function documentItem(): BelongsTo
    {
        return $this->belongsTo(SalesDocumentItem::class, 'sales_document_item_id');
    }
}
