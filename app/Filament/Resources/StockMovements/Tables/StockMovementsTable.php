<?php

namespace App\Filament\Resources\StockMovements\Tables;

use App\Models\StockMovement;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('المنتج')
                    ->searchable(),
                TextColumn::make('variant.name')
                    ->label('المتغير')
                    ->placeholder('—'),
                TextColumn::make('movement_number')
                    ->label('رقم الحركة')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('نوع الحركة')
                    ->formatStateUsing(fn (string $state): string => StockMovement::labels()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => in_array($state, [
                        StockMovement::TYPE_RECEIPT,
                        StockMovement::TYPE_TRANSFER_IN,
                        StockMovement::TYPE_PURCHASE,
                    ], true) ? 'success' : (in_array($state, [
                        StockMovement::TYPE_ISSUE,
                        StockMovement::TYPE_TRANSFER_OUT,
                        StockMovement::TYPE_PURCHASE_RETURN,
                    ], true) ? 'danger' : 'warning')),
                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state): string => $state >= 0 ? 'success' : 'danger')
                    ->weight('bold'),
                TextColumn::make('balance_after')
                    ->label('الرصيد بعد')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit_cost')
                    ->label('التكلفة')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('بواسطة')
                    ->placeholder('النظام'),
                TextColumn::make('moved_at')
                    ->label('التاريخ')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('type')
                    ->label('نوع الحركة')
                    ->options(StockMovement::labels()),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
            ])
            ->defaultSort('moved_at', 'desc');
    }
}
