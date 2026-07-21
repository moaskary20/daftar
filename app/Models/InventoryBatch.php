<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBatch extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'warehouse_id', 'product_id', 'product_variant_id', 'purchase_document_item_id',
        'batch_number', 'production_date', 'expiry_date', 'quantity', 'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'expiry_date' => 'date',
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseDocumentItem::class, 'purchase_document_item_id');
    }
}
