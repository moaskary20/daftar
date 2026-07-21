<?php

namespace App\Filament\Resources\ProductSerials\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductSerialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('المنتج')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('product_variant_id')
                    ->label('المتغير')
                    ->relationship('variant', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('serial_number')
                    ->label('الرقم التسلسلي')
                    ->unique(ignoreRecord: true)
                    ->required(),
                Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'available' => 'متاح',
                        'reserved' => 'محجوز',
                        'sold' => 'مباع',
                        'returned' => 'مرتجع',
                    ])
                    ->required()
                    ->default('available'),
                DatePicker::make('warranty_expires_at')->label('انتهاء الضمان'),
            ]);
    }
}
