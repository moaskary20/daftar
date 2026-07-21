<?php

namespace App\Filament\Resources\Stocktakes\Schemas;

use App\Models\Stocktake;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class StocktakeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الجرد')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        TextInput::make('number')
                            ->label('رقم الجرد')
                            ->placeholder('يُولّد تلقائياً')
                            ->unique(ignoreRecord: true),
                        Select::make('warehouse_id')
                            ->label('المخزن')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('type')
                            ->label('نوع الجرد')
                            ->options(Stocktake::typeLabels())
                            ->default(Stocktake::TYPE_PARTIAL)
                            ->required()
                            ->live(),
                        Select::make('status')
                            ->label('الحالة')
                            ->options(Stocktake::statusLabels())
                            ->default(Stocktake::STATUS_DRAFT)
                            ->disabled()
                            ->dehydrated(),
                        DatePicker::make('stocktake_date')
                            ->label('تاريخ الجرد')
                            ->default(today())
                            ->required(),
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('أصناف الجرد')
                    ->description(fn (Get $get): string => $get('type') === Stocktake::TYPE_FULL
                        ? 'تُضاف جميع أرصدة المخزن تلقائياً عند بدء الجرد الكامل.'
                        : 'اختر الأصناف المطلوب جردها.')
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
                                    ->live()
                                    ->disabled(fn (Get $get): bool => $get('../../status') !== Stocktake::STATUS_DRAFT),
                                Select::make('product_variant_id')
                                    ->label('المتغير')
                                    ->relationship('variant', 'name', fn ($query, Get $get) => $query->where('product_id', $get('product_id')))
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn (Get $get): bool => $get('../../status') !== Stocktake::STATUS_DRAFT),
                                TextInput::make('expected_quantity')
                                    ->label('رصيد النظام')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('counted_quantity')
                                    ->label('الكمية الفعلية')
                                    ->numeric(),
                                TextInput::make('difference_quantity')
                                    ->label('الفرق')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(),
                                Textarea::make('notes')
                                    ->label('ملاحظات'),
                            ])
                            ->columns(3)
                            ->addActionLabel('إضافة صنف للجرد')
                            ->addable(fn (Get $get): bool => $get('type') === Stocktake::TYPE_PARTIAL && $get('status') === Stocktake::STATUS_DRAFT)
                            ->deletable(fn (Get $get): bool => $get('type') === Stocktake::TYPE_PARTIAL && $get('status') === Stocktake::STATUS_DRAFT)
                            ->collapsible(),
                    ]),
            ]);
    }
}
