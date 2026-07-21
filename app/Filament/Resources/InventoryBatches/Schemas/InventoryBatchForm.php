<?php

namespace App\Filament\Resources\InventoryBatches\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InventoryBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('warehouse', 'name')
                    ->disabled()
                    ->required(),
                Select::make('product_id')
                    ->label('الصنف')
                    ->relationship('product', 'name')
                    ->disabled()
                    ->required(),
                Select::make('product_variant_id')
                    ->label('المتغير')
                    ->relationship('variant', 'name')
                    ->disabled(),
                TextInput::make('batch_number')
                    ->label('رقم الدفعة')
                    ->required(),
                DatePicker::make('production_date')->label('تاريخ الإنتاج'),
                DatePicker::make('expiry_date')->label('تاريخ الصلاحية')->afterOrEqual('production_date'),
                TextInput::make('quantity')
                    ->label('الكمية')
                    ->required()
                    ->numeric()
                    ->disabled()
                    ->default(0),
                TextInput::make('unit_cost')
                    ->label('تكلفة الوحدة')
                    ->required()
                    ->numeric()
                    ->disabled()
                    ->default(0)
                    ->prefix('ج.م'),
            ]);
    }
}
