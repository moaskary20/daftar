<?php

namespace App\Filament\Resources\StockTransfers\Schemas;

use App\Models\StockTransfer;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class StockTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات التحويل')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->schema([
                        TextInput::make('number')
                            ->label('رقم التحويل')
                            ->placeholder('يُولّد تلقائياً')
                            ->unique(ignoreRecord: true),
                        Select::make('from_warehouse_id')
                            ->label('من مخزن')
                            ->relationship('fromWarehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Select::make('to_warehouse_id')
                            ->label('إلى مخزن')
                            ->relationship('toWarehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->different('from_warehouse_id')
                            ->required(),
                        DatePicker::make('transfer_date')
                            ->label('تاريخ التحويل')
                            ->default(today())
                            ->required(),
                        Select::make('status')
                            ->label('الحالة')
                            ->options(StockTransfer::statusLabels())
                            ->default(StockTransfer::STATUS_DRAFT)
                            ->disabled()
                            ->dehydrated(),
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('أصناف التحويل')
                    ->schema([
                        Repeater::make('items')
                            ->label('')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->label('المنتج')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),
                                Select::make('product_variant_id')
                                    ->label('المتغير')
                                    ->relationship('variant', 'name', fn ($query, Get $get) => $query->where('product_id', $get('product_id')))
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('quantity')
                                    ->label('الكمية')
                                    ->numeric()
                                    ->minValue(0.001)
                                    ->required(),
                            ])
                            ->columns(3)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('إضافة صنف')
                            ->collapsible(),
                    ]),
            ]);
    }
}
