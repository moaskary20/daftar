<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use App\Models\StockMovement;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('حركة المخزون')
                    ->description('إضافة أو صرف أو تسوية رصيد صنف في مخزن')
                    ->icon('heroicon-o-arrows-right-left')
                    ->schema([
                        Select::make('type')
                            ->label('نوع الحركة')
                            ->options([
                                StockMovement::TYPE_RECEIPT => 'إضافة مخزون',
                                StockMovement::TYPE_ISSUE => 'صرف مخزون',
                                StockMovement::TYPE_ADJUSTMENT => 'تسوية مخزون (+ أو -)',
                            ])
                            ->required()
                            ->live(),
                        Select::make('warehouse_id')
                            ->label('المخزن')
                            ->relationship('warehouse', 'name', fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('product_id')
                            ->label('المنتج')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Select::make('product_variant_id')
                            ->label('المتغير')
                            ->relationship('variant', 'name', fn ($query, $get) => $query->where('product_id', $get('product_id')))
                            ->searchable()
                            ->preload(),
                        TextInput::make('quantity')
                            ->label('الكمية')
                            ->helperText('في التسوية استخدم قيمة سالبة للخصم')
                            ->required()
                            ->numeric()
                            ->notIn([0]),
                        TextInput::make('unit_cost')
                            ->label('تكلفة الوحدة')
                            ->numeric()
                            ->prefix('ج.م'),
                        DateTimePicker::make('moved_at')
                            ->label('تاريخ الحركة')
                            ->default(now())
                            ->required(),
                        Textarea::make('notes')
                            ->label('البيان / الملاحظات')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
