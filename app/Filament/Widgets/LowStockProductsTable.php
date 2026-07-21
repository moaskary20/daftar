<?php

namespace App\Filament\Widgets;

use App\Models\WarehouseStock;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockProductsTable extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = ['lg' => 2];

    public function table(Table $table): Table
    {
        return $table
            ->heading('المنتجات قليلة المخزون')
            ->description('كميات وصلت إلى حد إعادة الطلب أو أقل')
            ->query(fn (): Builder => WarehouseStock::query()
                ->with(['product', 'variant', 'warehouse'])
                ->whereColumn('quantity', '<=', 'reorder_level')
                ->orderBy('quantity'))
            ->columns([
                TextColumn::make('product.name')
                    ->label('المنتج')
                    ->weight('bold'),
                TextColumn::make('variant.name')
                    ->label('المتغير')
                    ->placeholder('-'),
                TextColumn::make('warehouse.name')
                    ->label('المخزن'),
                TextColumn::make('quantity')
                    ->label('الكمية الحالية')
                    ->numeric(decimalPlaces: 2)
                    ->badge()
                    ->color(fn ($state): string => (float) $state <= 0 ? 'danger' : 'warning'),
                TextColumn::make('reorder_level')
                    ->label('حد إعادة الطلب')
                    ->numeric(decimalPlaces: 2),
            ])
            ->emptyStateHeading('المخزون بحالة جيدة')
            ->emptyStateDescription('لا توجد منتجات وصلت إلى حد إعادة الطلب.')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
