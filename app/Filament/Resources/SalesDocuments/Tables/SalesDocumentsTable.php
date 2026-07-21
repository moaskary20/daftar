<?php

namespace App\Filament\Resources\SalesDocuments\Tables;

use App\Models\SalesDocument;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable(),
                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable(),
                TextColumn::make('number')
                    ->label('رقم المستند')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state): string => SalesDocument::typeLabels()[$state] ?? $state)
                    ->badge(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state): string => SalesDocument::statusLabels()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        SalesDocument::STATUS_POSTED, SalesDocument::STATUS_DELIVERED => 'success',
                        SalesDocument::STATUS_PARTIAL => 'warning',
                        SalesDocument::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('document_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                TextColumn::make('expected_date')
                    ->label('موعد التسليم')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('grand_total')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')->label('النوع')->options(SalesDocument::typeLabels()),
                SelectFilter::make('status')->label('الحالة')->options(SalesDocument::statusLabels()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (SalesDocument $record): bool => ! in_array($record->status, [
                        SalesDocument::STATUS_POSTED,
                        SalesDocument::STATUS_DELIVERED,
                        SalesDocument::STATUS_CANCELLED,
                    ], true)),
            ]);
    }
}
