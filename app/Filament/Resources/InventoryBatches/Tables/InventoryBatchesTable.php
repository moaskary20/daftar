<?php

namespace App\Filament\Resources\InventoryBatches\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('الصنف')
                    ->searchable(),
                TextColumn::make('variant.name')
                    ->label('المتغير'),
                TextColumn::make('batch_number')
                    ->label('رقم الدفعة')
                    ->searchable(),
                TextColumn::make('production_date')
                    ->label('تاريخ الإنتاج')
                    ->date()
                    ->sortable(),
                TextColumn::make('expiry_date')
                    ->label('تاريخ الصلاحية')
                    ->date()
                    ->sortable()
                    ->color(fn ($state): string => $state?->isBefore(today()->addDays(30)) ? 'danger' : 'gray'),
                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                TextColumn::make('unit_cost')
                    ->label('التكلفة')
                    ->money('EGP')
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
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
