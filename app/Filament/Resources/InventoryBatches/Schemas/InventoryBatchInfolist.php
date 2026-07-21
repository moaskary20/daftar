<?php

namespace App\Filament\Resources\InventoryBatches\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InventoryBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('warehouse.name')
                    ->label('Warehouse'),
                TextEntry::make('product.name')
                    ->label('Product'),
                TextEntry::make('product_variant_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('purchase_document_item_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('batch_number'),
                TextEntry::make('production_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('expiry_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('quantity')
                    ->numeric(),
                TextEntry::make('unit_cost')
                    ->money(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
