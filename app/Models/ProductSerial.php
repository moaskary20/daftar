<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSerial extends Model
{
    protected $fillable = [
        'product_id', 'product_variant_id', 'warehouse_id',
        'sales_document_item_id', 'serial_number', 'status', 'warranty_expires_at',
    ];

    protected function casts(): array
    {
        return ['warranty_expires_at' => 'date'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function salesItem(): BelongsTo
    {
        return $this->belongsTo(SalesDocumentItem::class, 'sales_document_item_id');
    }
}
