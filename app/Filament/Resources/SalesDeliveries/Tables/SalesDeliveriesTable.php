<?php

namespace App\Filament\Resources\SalesDeliveries\Tables;

use App\Models\SalesDelivery;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesDeliveriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('رقم التسليم')->searchable(),
                TextColumn::make('document.number')->label('أمر البيع')->searchable(),
                TextColumn::make('document.customer.name')->label('العميل')->searchable(),
                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state): string => SalesDelivery::statusLabels()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state): string => $state === SalesDelivery::STATUS_COMPLETED ? 'success' : 'gray'),
                TextColumn::make('delivery_date')
                    ->label('تاريخ التسليم')
                    ->date()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('تاريخ الاعتماد')
                    ->dateTime()
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
                SelectFilter::make('status')->label('الحالة')->options(SalesDelivery::statusLabels()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (SalesDelivery $record): bool => $record->status === SalesDelivery::STATUS_DRAFT),
            ]);
    }
}
