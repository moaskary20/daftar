<?php

namespace App\Filament\Resources\Stocktakes\Tables;

use App\Models\Stocktake;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StocktakesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable(),
                TextColumn::make('number')
                    ->label('رقم الجرد')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => Stocktake::typeLabels()[$state] ?? $state)
                    ->badge()
                    ->color('info'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => Stocktake::statusLabels()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Stocktake::STATUS_COMPLETED => 'success',
                        Stocktake::STATUS_CANCELLED => 'danger',
                        Stocktake::STATUS_COUNTING => 'info',
                        default => 'warning',
                    }),
                TextColumn::make('stocktake_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('الأصناف')
                    ->counts('items'),
                TextColumn::make('creator.name')
                    ->label('بواسطة')
                    ->placeholder('النظام'),
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
                SelectFilter::make('type')->label('النوع')->options(Stocktake::typeLabels()),
                SelectFilter::make('status')->label('الحالة')->options(Stocktake::statusLabels()),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                EditAction::make()
                    ->label('متابعة الجرد')
                    ->visible(fn (Stocktake $record): bool => ! in_array($record->status, [
                        Stocktake::STATUS_COMPLETED,
                        Stocktake::STATUS_CANCELLED,
                    ], true)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('stocktake_date', 'desc');
    }
}
