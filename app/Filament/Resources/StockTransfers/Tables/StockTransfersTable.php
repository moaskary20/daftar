<?php

namespace App\Filament\Resources\StockTransfers\Tables;

use App\Models\StockTransfer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fromWarehouse.name')
                    ->label('من مخزن')
                    ->searchable(),
                TextColumn::make('toWarehouse.name')
                    ->label('إلى مخزن')
                    ->searchable(),
                TextColumn::make('number')
                    ->label('رقم التحويل')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => StockTransfer::statusLabels()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        StockTransfer::STATUS_COMPLETED => 'success',
                        StockTransfer::STATUS_CANCELLED => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('transfer_date')
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
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(StockTransfer::statusLabels()),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                EditAction::make()
                    ->label('تعديل')
                    ->visible(fn (StockTransfer $record): bool => $record->status === StockTransfer::STATUS_DRAFT),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('transfer_date', 'desc');
    }
}
